<?php

namespace Tests\Unit;

use App\Services\Prod04WorkingCalendar;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Prod04WorkingCalendarTest extends TestCase
{
    public function test_production_calendar_matches_the_approved_jakarta_schedule(): void
    {
        $calendar = config('prod04.working_calendar');
        $expectedIntervals = [['08:30', '12:00'], ['13:00', '17:30']];

        $this->assertSame('Asia/Jakarta', $calendar['timezone']);
        $this->assertSame(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], array_keys($calendar['weekly_intervals']));
        foreach ($calendar['weekly_intervals'] as $intervals) {
            $this->assertSame($expectedIntervals, $intervals);
        }
    }

    public function test_verified_national_holiday_dataset_has_official_dates_and_checksum(): void
    {
        $calendar = app(Prod04WorkingCalendar::class)->configuration();

        $this->assertSame([2026, 2027], $calendar['holiday_covered_years']);
        $this->assertCount(35, $calendar['national_holidays']);
        $this->assertContains('2026-03-21', $calendar['national_holidays']);
        $this->assertContains('2027-03-10', $calendar['national_holidays']);
        $this->assertNotContains('2026-05-28', $calendar['national_holidays']); // Cuti bersama.
        $this->assertNotContains('2027-03-12', $calendar['national_holidays']); // Cuti bersama.
    }

    public function test_official_national_holiday_and_cross_year_calculation(): void
    {
        $calendar = app(Prod04WorkingCalendar::class);

        $this->assertSame(0, $calendar->workingMinutesBetween(
            new \DateTimeImmutable('2027-03-10 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2027-03-10 17:30:00 Asia/Jakarta'),
        ));
        $this->assertSame(960, $calendar->workingMinutesBetween(
            new \DateTimeImmutable('2026-12-31 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2027-01-04 17:30:00 Asia/Jakarta'),
        )); // 31 Des dan 4 Jan masing-masing 480 menit kerja.
    }

    public function test_approved_weekday_intervals_exclude_the_lunch_break(): void
    {
        config(['prod04.working_calendar' => $this->calendar()]);

        $minutes = app(Prod04WorkingCalendar::class)->workingMinutesBetween(
            new \DateTimeImmutable('2026-10-21 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2026-10-21 17:30:00 Asia/Jakarta'),
        );

        $this->assertSame(480, $minutes);
    }

    public function test_weekends_and_configured_national_holidays_are_excluded(): void
    {
        config(['prod04.working_calendar' => $this->calendar(['2026-10-21'])]);
        $calendar = app(Prod04WorkingCalendar::class);

        $this->assertSame(0, $calendar->workingMinutesBetween(
            new \DateTimeImmutable('2026-10-21 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2026-10-21 17:30:00 Asia/Jakarta'),
        ));
        $this->assertSame(0, $calendar->workingMinutesBetween(
            new \DateTimeImmutable('2026-10-24 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2026-10-24 17:30:00 Asia/Jakarta'),
        ));
    }

    public function test_calendar_fails_closed_without_verified_holiday_data(): void
    {
        $calendar = config('prod04.working_calendar');
        $calendar['holiday_source'] = null;
        $calendar['holiday_data_checksum'] = null;
        $calendar['holiday_verified_at'] = null;
        $calendar['holiday_covered_years'] = [];
        config(['prod04.working_calendar' => $calendar]);

        $this->expectException(ValidationException::class);
        app(Prod04WorkingCalendar::class)->workingMinutesBetween(
            new \DateTimeImmutable('2026-10-21 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2026-10-21 17:30:00 Asia/Jakarta'),
        );
    }

    public function test_calendar_fails_closed_when_holiday_data_does_not_cover_the_year(): void
    {
        config(['prod04.working_calendar' => $this->calendar([], [2025])]);

        $this->expectException(ValidationException::class);
        app(Prod04WorkingCalendar::class)->workingMinutesBetween(
            new \DateTimeImmutable('2026-10-21 08:30:00 Asia/Jakarta'),
            new \DateTimeImmutable('2026-10-21 17:30:00 Asia/Jakarta'),
        );
    }

    public function test_calendar_rejects_a_holiday_dataset_with_a_mismatched_checksum(): void
    {
        $calendar = $this->calendar(['2026-10-21']);
        $calendar['holiday_data_checksum'] = str_repeat('0', 64);
        config(['prod04.working_calendar' => $calendar]);

        $this->expectException(ValidationException::class);
        app(Prod04WorkingCalendar::class)->configuration();
    }

    /**
     * @param  array<int, string>  $holidays
     * @param  array<int, int>  $coveredYears
     * @return array<string, mixed>
     */
    private function calendar(array $holidays = [], array $coveredYears = [2026]): array
    {
        $intervals = ['08:30', '12:00'];

        return [
            'timezone' => 'Asia/Jakarta',
            'weekly_intervals' => [
                'monday' => [$intervals, ['13:00', '17:30']],
                'tuesday' => [$intervals, ['13:00', '17:30']],
                'wednesday' => [$intervals, ['13:00', '17:30']],
                'thursday' => [$intervals, ['13:00', '17:30']],
                'friday' => [$intervals, ['13:00', '17:30']],
            ],
            'national_holidays' => $holidays,
            'holiday_source' => 'Unit-test fixture',
            'holiday_data_checksum' => hash('sha256', json_encode($holidays)),
            'holiday_verified_at' => '2026-01-01',
            'holiday_covered_years' => $coveredYears,
        ];
    }
}
