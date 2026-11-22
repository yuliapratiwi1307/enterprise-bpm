<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $violations = DB::table('mm_supplier_quotes')
            ->where('approval_status', 'REJECTED')
            ->where(function ($query): void {
                $query->whereNull('rejection_reason')
                    ->orWhereRaw("rejection_reason ~ '^[[:space:]]*$'");
            })
            ->count();

        if ($violations > 0) {
            throw new RuntimeException(sprintf(
                'Cannot reject whitespace-only supplier quote reasons; %d historical row(s) violate the constraint.',
                $violations,
            ));
        }

        DB::statement(<<<'SQL'
            ALTER TABLE mm_supplier_quotes
            ADD CONSTRAINT mm_supplier_quotes_rejection_reason_whitespace_check
            CHECK (
                approval_status <> 'REJECTED'
                OR rejection_reason !~ '^[[:space:]]*$'
            )
            SQL);
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE mm_supplier_quotes DROP CONSTRAINT mm_supplier_quotes_rejection_reason_whitespace_check'
        );
    }
};
