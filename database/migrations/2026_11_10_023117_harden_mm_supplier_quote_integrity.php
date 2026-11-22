<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $orphanCounts = DB::selectOne(<<<'SQL'
            SELECT
                (SELECT COUNT(*) FROM mm_supplier_quotes q
                    LEFT JOIN users u ON u.id = q.approved_by
                    WHERE q.approved_by IS NOT NULL AND u.id IS NULL) AS approved_by_orphans,
                (SELECT COUNT(*) FROM mm_supplier_quotes q
                    LEFT JOIN users u ON u.id = q.rejected_by
                    WHERE q.rejected_by IS NOT NULL AND u.id IS NULL) AS rejected_by_orphans,
                (SELECT COUNT(*) FROM mm_supplier_quote_approval_events e
                    LEFT JOIN users u ON u.id = e.actor_id
                    WHERE e.actor_id IS NOT NULL AND u.id IS NULL) AS actor_id_orphans
            SQL);
        $rejectionViolations = (int) DB::table('mm_supplier_quotes')
            ->where(function ($query): void {
                $query->where(function ($rejected): void {
                    $rejected->where('approval_status', 'REJECTED')
                        ->where(function ($reason): void {
                            $reason->whereNull('rejection_reason')
                                ->orWhereRaw("BTRIM(rejection_reason) = ''");
                        });
                })->orWhere(function ($nonRejected): void {
                    $nonRejected->whereIn('approval_status', ['PENDING', 'APPROVED'])
                        ->whereNotNull('rejection_reason');
                });
            })
            ->count();

        if (
            (int) $orphanCounts->approved_by_orphans > 0
            || (int) $orphanCounts->rejected_by_orphans > 0
            || (int) $orphanCounts->actor_id_orphans > 0
        ) {
            throw new RuntimeException(sprintf(
                'Cannot add supplier quote actor foreign keys; orphan counts: approved_by=%d, rejected_by=%d, actor_id=%d.',
                $orphanCounts->approved_by_orphans,
                $orphanCounts->rejected_by_orphans,
                $orphanCounts->actor_id_orphans,
            ));
        }

        if ($rejectionViolations > 0) {
            throw new RuntimeException(sprintf(
                'Cannot tighten supplier quote rejection reason constraint; %d historical row(s) violate it.',
                $rejectionViolations,
            ));
        }

        Schema::table('mm_supplier_quotes', function (Blueprint $table): void {
            $table->foreign('approved_by', 'mm_quote_approved_by_users_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('rejected_by', 'mm_quote_rejected_by_users_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('mm_supplier_quote_approval_events', function (Blueprint $table): void {
            $table->foreign('actor_id', 'mm_quote_approval_actor_users_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->string('event_type', 16)->nullable();
            $table->boolean('previous_is_active')->nullable();
            $table->boolean('new_is_active')->nullable();
        });

        DB::table('mm_supplier_quote_approval_events')
            ->whereNull('event_type')
            ->update(['event_type' => DB::raw('to_status')]);
        DB::statement("ALTER TABLE mm_supplier_quote_approval_events ALTER COLUMN event_type SET DEFAULT 'PENDING'");
        DB::statement('ALTER TABLE mm_supplier_quote_approval_events ALTER COLUMN event_type SET NOT NULL');

        DB::statement('ALTER TABLE mm_supplier_quotes DROP CONSTRAINT mm_supplier_quotes_approval_metadata_check');
        DB::statement(<<<'SQL'
            ALTER TABLE mm_supplier_quotes
            ADD CONSTRAINT mm_supplier_quotes_approval_metadata_check
            CHECK (
                (approval_status = 'PENDING'
                    AND approved_by IS NULL AND approved_at IS NULL
                    AND rejected_by IS NULL AND rejected_at IS NULL
                    AND rejection_reason IS NULL)
                OR
                (approval_status = 'APPROVED'
                    AND approved_by IS NOT NULL AND approved_at IS NOT NULL
                    AND rejected_by IS NULL AND rejected_at IS NULL
                    AND rejection_reason IS NULL)
                OR
                (approval_status = 'REJECTED'
                    AND approved_by IS NULL AND approved_at IS NULL
                    AND rejected_by IS NOT NULL AND rejected_at IS NOT NULL
                    AND rejection_reason IS NOT NULL
                    AND BTRIM(rejection_reason) <> '')
            )
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE mm_supplier_quote_approval_events
            ADD CONSTRAINT mm_quote_event_type_check
            CHECK (event_type IN ('PENDING', 'APPROVED', 'REJECTED', 'ACTIVATED', 'DEACTIVATED'))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE mm_supplier_quote_approval_events
            ADD CONSTRAINT mm_quote_activation_event_state_check
            CHECK (
                (event_type IN ('ACTIVATED', 'DEACTIVATED')
                    AND previous_is_active IS NOT NULL
                    AND new_is_active IS NOT NULL
                    AND previous_is_active <> new_is_active)
                OR
                (event_type NOT IN ('ACTIVATED', 'DEACTIVATED')
                    AND previous_is_active IS NULL
                    AND new_is_active IS NULL)
            )
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('mm_supplier_quote_approval_events')
            ->whereIn('event_type', ['ACTIVATED', 'DEACTIVATED'])
            ->exists()) {
            throw new RuntimeException(
                'Cannot roll back MM quote integrity migration after activation audit events have been recorded.',
            );
        }

        DB::statement('ALTER TABLE mm_supplier_quote_approval_events DROP CONSTRAINT mm_quote_activation_event_state_check');
        DB::statement('ALTER TABLE mm_supplier_quote_approval_events DROP CONSTRAINT mm_quote_event_type_check');
        DB::statement('ALTER TABLE mm_supplier_quotes DROP CONSTRAINT mm_supplier_quotes_approval_metadata_check');
        DB::statement(<<<'SQL'
            ALTER TABLE mm_supplier_quotes
            ADD CONSTRAINT mm_supplier_quotes_approval_metadata_check
            CHECK (
                (approval_status = 'PENDING'
                    AND approved_by IS NULL AND approved_at IS NULL
                    AND rejected_by IS NULL AND rejected_at IS NULL AND rejection_reason IS NULL)
                OR
                (approval_status = 'APPROVED'
                    AND approved_by IS NOT NULL AND approved_at IS NOT NULL
                    AND rejected_by IS NULL AND rejected_at IS NULL AND rejection_reason IS NULL)
                OR
                (approval_status = 'REJECTED'
                    AND approved_by IS NULL AND approved_at IS NULL
                    AND rejected_by IS NOT NULL AND rejected_at IS NOT NULL)
            )
            SQL);

        Schema::table('mm_supplier_quote_approval_events', function (Blueprint $table): void {
            $table->dropForeign('mm_quote_approval_actor_users_fk');
            $table->dropColumn(['event_type', 'previous_is_active', 'new_is_active']);
        });
        Schema::table('mm_supplier_quotes', function (Blueprint $table): void {
            $table->dropForeign('mm_quote_approved_by_users_fk');
            $table->dropForeign('mm_quote_rejected_by_users_fk');
        });
    }
};
