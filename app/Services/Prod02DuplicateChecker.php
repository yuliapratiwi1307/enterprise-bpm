<?php

namespace App\Services;

use App\Models\ClientBrief;

class Prod02DuplicateChecker
{
    private const ALGORITHM_VERSION = 'prod02-duplicate-v1';

    private const TEXT_FIELDS = [
        'client_name' => 'Klien',
        'requirement_type' => 'Jenis pekerjaan',
        'work_location' => 'Lokasi',
        'description' => 'Deskripsi',
    ];

    private const INPUT_FIELDS = [
        'client_name',
        'requirement_type',
        'work_location',
        'target_date',
        'description',
        'communication_source',
    ];

    /**
     * @return array{algorithm_version: string, fingerprint: string, input: array<string, mixed>, candidates: array<int, array<string, mixed>>}
     */
    public function findCandidates(ClientBrief $brief): array
    {
        $input = $this->inputSnapshot($brief);
        $scoredCandidates = ClientBrief::query()
            ->where('status', 'RECORDED')
            ->where('id', '<>', $brief->getKey())
            ->get([
                'id',
                'request_number',
                'status',
                'client_name',
                'requirement_type',
                'work_location',
                'target_date',
                'description',
            ])
            ->map(fn (ClientBrief $candidate): ?array => $this->scoreCandidate($input, $candidate))
            ->filter()
            ->sort(function (array $left, array $right): int {
                return ($right['score'] <=> $left['score']) ?: ($left['id'] <=> $right['id']);
            })
            ->take(10)
            ->values();

        return [
            'algorithm_version' => self::ALGORITHM_VERSION,
            'fingerprint' => $this->fingerprint($input),
            'input' => $input,
            'candidates' => $scoredCandidates->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inputSnapshot(ClientBrief $brief): array
    {
        $input = [];

        foreach (self::INPUT_FIELDS as $field) {
            $input[$field] = $field === 'target_date'
                ? $brief->target_date?->format('Y-m-d')
                : $brief->{$field};
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function fingerprint(array $input): string
    {
        return hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    private function scoreCandidate(array $input, ClientBrief $candidate): ?array
    {
        $similarities = [];
        $reasons = [];

        foreach (self::TEXT_FIELDS as $field => $label) {
            $similarity = $this->textSimilarity($input[$field] ?? null, $candidate->{$field});

            if ($similarity === null) {
                continue;
            }

            $similarities[$field] = $similarity;

            if ($similarity >= 0.5) {
                $reasons[] = [
                    'field' => $field,
                    'label' => $label,
                    'similarity' => (int) round($similarity * 100),
                ];
            }
        }

        $inputTargetPeriod = $this->targetPeriod($input['target_date'] ?? null);
        $candidateTargetPeriod = $this->targetPeriod($candidate->target_date?->format('Y-m-d'));

        if ($inputTargetPeriod !== null && $candidateTargetPeriod !== null) {
            $targetMatches = $inputTargetPeriod === $candidateTargetPeriod;
            $similarities['target_date'] = $targetMatches ? 1.0 : 0.0;

            if ($targetMatches) {
                $reasons[] = [
                    'field' => 'target_date',
                    'label' => 'Periode target',
                    'similarity' => 100,
                ];
            }
        }

        if (count($reasons) < 2 || $similarities === []) {
            return null;
        }

        return [
            'id' => $candidate->id,
            'request_number' => $candidate->request_number,
            'status' => $candidate->status,
            'client_name' => $candidate->client_name,
            'requirement_type' => $candidate->requirement_type,
            'work_location' => $candidate->work_location,
            'target_date' => $candidate->target_date?->format('Y-m-d'),
            'description' => $candidate->description,
            'score' => (int) round(array_sum($similarities) / count($similarities) * 100),
            'reasons' => $reasons,
        ];
    }

    private function textSimilarity(?string $left, ?string $right): ?float
    {
        $leftTokens = $this->tokens($left);
        $rightTokens = $this->tokens($right);

        if ($leftTokens === [] || $rightTokens === []) {
            return null;
        }

        $intersection = count(array_intersect($leftTokens, $rightTokens));

        return (2 * $intersection) / (count($leftTokens) + count($rightTokens));
    }

    /**
     * @return array<int, string>
     */
    private function tokens(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $normalized = mb_strtolower($value, 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized);

        if ($normalized === null) {
            return [];
        }

        $tokens = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return [];
        }

        $tokens = array_values(array_unique($tokens));
        sort($tokens, SORT_STRING);

        return $tokens;
    }

    private function targetPeriod(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        return substr($date, 0, 7);
    }
}
