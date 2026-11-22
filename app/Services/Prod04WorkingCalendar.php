<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

class Prod04WorkingCalendar
{
    private const WEEKDAYS = [
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 7,
    ];

    /**
     * @return array{timezone: string, weekly_intervals: array<string, array<int, array{0: string, 1: string}>>, national_holidays: array<int, string>, holiday_source: string, holiday_data_checksum: string, holiday_verified_at: string, holiday_covered_years: array<int, int>}
     */
    public function configuration(): array
    {
        $calendar = config('prod04.working_calendar');

        if (
            ! is_array($calendar)
            || ! is_string($calendar['timezone'] ?? null)
            || ! is_array($calendar['weekly_intervals'] ?? null)
            || ! is_array($calendar['national_holidays'] ?? null)
            || ! is_string($calendar['holiday_source'] ?? null)
            || blank($calendar['holiday_source'])
            || ! is_string($calendar['holiday_data_checksum'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/i', $calendar['holiday_data_checksum'])
            || ! is_string($calendar['holiday_verified_at'] ?? null)
            || ! is_array($calendar['holiday_covered_years'] ?? null)
            || $calendar['weekly_intervals'] === []
            || $calendar['holiday_covered_years'] === []
        ) {
            throw ValidationException::withMessages([
                'distribution' => 'Kalender kerja PROD-04 atau dataset hari libur terverifikasi belum tersedia; operasi diblokir.',
            ]);
        }

        try {
            new \DateTimeZone($calendar['timezone']);
        } catch (\DateInvalidTimeZoneException) {
            throw ValidationException::withMessages([
                'distribution' => 'Zona waktu kalender kerja PROD-04 tidak valid.',
            ]);
        }

        $verifiedAt = \DateTimeImmutable::createFromFormat('!Y-m-d', $calendar['holiday_verified_at']);
        if ($verifiedAt === false || $verifiedAt->format('Y-m-d') !== $calendar['holiday_verified_at']) {
            throw ValidationException::withMessages([
                'distribution' => 'Waktu verifikasi dataset hari libur PROD-04 tidak valid.',
            ]);
        }

        foreach ($calendar['holiday_covered_years'] as $year) {
            if (! is_int($year) || $year < 2000 || $year > 9999) {
                throw ValidationException::withMessages([
                    'distribution' => 'Cakupan tahun dataset hari libur PROD-04 tidak valid.',
                ]);
            }
        }

        foreach ($calendar['weekly_intervals'] as $weekday => $intervals) {
            if (! array_key_exists(strtolower((string) $weekday), self::WEEKDAYS) || ! is_array($intervals)) {
                throw ValidationException::withMessages([
                    'distribution' => 'Jadwal kalender kerja PROD-04 tidak valid.',
                ]);
            }

            $previousEnd = null;
            foreach ($intervals as $interval) {
                if (
                    ! is_array($interval)
                    || count($interval) !== 2
                    || ! is_string($interval[0] ?? null)
                    || ! is_string($interval[1] ?? null)
                    || ! preg_match('/^\d{2}:\d{2}$/', $interval[0])
                    || ! preg_match('/^\d{2}:\d{2}$/', $interval[1])
                    || ! $this->isValidTime($interval[0])
                    || ! $this->isValidTime($interval[1])
                    || $interval[0] >= $interval[1]
                    || ($previousEnd !== null && $interval[0] < $previousEnd)
                ) {
                    throw ValidationException::withMessages([
                        'distribution' => 'Interval jam kerja PROD-04 harus berurutan, tidak tumpang tindih, dan valid.',
                    ]);
                }

                $previousEnd = $interval[1];
            }
        }

        // Jangan terima duplikasi/urutan acak: checksum dataset harus stabil dan dapat diaudit.
        if (count($calendar['national_holidays']) !== count(array_unique($calendar['national_holidays']))
            || $calendar['national_holidays'] !== array_values($calendar['national_holidays'])
            || $calendar['national_holidays'] !== $this->sortedHolidays($calendar['national_holidays'])) {
            throw ValidationException::withMessages([
                'distribution' => 'Daftar hari libur PROD-04 harus unik dan terurut.',
            ]);
        }

        foreach ($calendar['national_holidays'] as $holiday) {
            if (! is_string($holiday) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $holiday)) {
                throw ValidationException::withMessages([
                    'distribution' => 'Daftar hari libur PROD-04 tidak valid.',
                ]);
            }

            $date = CarbonImmutable::createFromFormat('!Y-m-d', $holiday);
            if ($date === false || $date->format('Y-m-d') !== $holiday || ! in_array((int) $date->year, $calendar['holiday_covered_years'], true)) {
                throw ValidationException::withMessages([
                    'distribution' => 'Tanggal hari libur PROD-04 tidak valid.',
                ]);
            }
        }

        if (! hash_equals(
            hash('sha256', json_encode($calendar['national_holidays'], JSON_THROW_ON_ERROR)),
            strtolower($calendar['holiday_data_checksum']),
        )) {
            throw ValidationException::withMessages([
                'distribution' => 'Checksum dataset hari libur PROD-04 tidak cocok dengan daftar tanggal.',
            ]);
        }

        return $calendar;
    }

    /** @param array<int, string> $holidays
     *  @return array<int, string>
     */
    private function sortedHolidays(array $holidays): array
    {
        sort($holidays, SORT_STRING);

        return $holidays;
    }

    private function isValidTime(string $time): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour < 24 && $minute < 60;
    }

    public function workingMinutesBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $calendar = $this->configuration();
        $timezone = $calendar['timezone'];
        $from = CarbonImmutable::instance($start)->setTimezone($timezone);
        $until = CarbonImmutable::instance($end)->setTimezone($timezone);

        if ($until->lessThanOrEqualTo($from)) {
            return 0;
        }

        $coveredYears = array_fill_keys($calendar['holiday_covered_years'], true);
        for ($year = (int) $from->format('Y'); $year <= (int) $until->format('Y'); $year++) {
            if (! isset($coveredYears[$year])) {
                throw ValidationException::withMessages([
                    'distribution' => "Dataset hari libur nasional terverifikasi belum mencakup tahun {$year}.",
                ]);
            }
        }

        $holidays = array_fill_keys($calendar['national_holidays'], true);
        $minutes = 0;

        for ($date = $from->startOfDay(); $date->lessThanOrEqualTo($until->startOfDay()); $date = $date->addDay()) {
            $weekday = strtolower($date->englishDayOfWeek);
            if (isset($holidays[$date->toDateString()])) {
                continue;
            }

            foreach ($calendar['weekly_intervals'][$weekday] ?? [] as [$startTime, $endTime]) {
                $intervalStart = $date->setTimeFromTimeString($startTime);
                $intervalEnd = $date->setTimeFromTimeString($endTime);
                $overlapStart = $from->greaterThan($intervalStart) ? $from : $intervalStart;
                $overlapEnd = $until->lessThan($intervalEnd) ? $until : $intervalEnd;

                if ($overlapEnd->greaterThan($overlapStart)) {
                    $minutes += intdiv($overlapEnd->getTimestamp() - $overlapStart->getTimestamp(), 60);
                }
            }
        }

        return $minutes;
    }
}
