<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Prod02RequestNumberIssuer
{
    public function lockRecordedCandidateSet(?string $connectionName = null): void
    {
        DB::connection($connectionName)->select(
            'SELECT pg_advisory_xact_lock(1347569492, 2)'
        );
    }

    public function nextNumber(int $year, ?string $connectionName = null): int
    {
        $row = DB::connection($connectionName)->selectOne(
            <<<'SQL'
                INSERT INTO request_number_sequences (year, last_number, created_at, updated_at)
                VALUES (?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (year) DO UPDATE
                SET last_number = request_number_sequences.last_number + 1,
                    updated_at = CURRENT_TIMESTAMP
                RETURNING last_number
                SQL,
            [$year]
        );

        return (int) $row->last_number;
    }

    public function format(int $year, int $number): string
    {
        return sprintf('REQ-%04d-%04d', $year, $number);
    }
}
