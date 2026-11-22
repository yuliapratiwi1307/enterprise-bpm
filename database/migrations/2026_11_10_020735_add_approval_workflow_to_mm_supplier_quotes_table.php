<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mm_supplier_quotes', function (Blueprint $table) {
            $table->string('approval_status', 16)->default('PENDING');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestampTz('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->index(['approval_status', 'is_active'], 'mm_quote_approval_active_idx');
        });

        DB::statement(
            "ALTER TABLE mm_supplier_quotes ADD CONSTRAINT mm_supplier_quotes_approval_status_check CHECK (approval_status IN ('PENDING', 'APPROVED', 'REJECTED'))"
        );
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

        Schema::create('mm_supplier_quote_approval_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('quote_id')
                ->constrained('mm_supplier_quotes')
                ->restrictOnDelete();
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestampTz('acted_at');
            $table->text('reason')->nullable();
            $table->index(['quote_id', 'acted_at'], 'mm_quote_approval_events_timeline_idx');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE mm_supplier_quote_approval_events
            ADD CONSTRAINT mm_supplier_quote_approval_events_status_check
            CHECK (
                (from_status IS NULL OR from_status IN ('PENDING', 'APPROVED', 'REJECTED'))
                AND to_status IN ('PENDING', 'APPROVED', 'REJECTED')
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('mm_supplier_quote_approval_events');
        DB::statement('ALTER TABLE mm_supplier_quotes DROP CONSTRAINT mm_supplier_quotes_approval_metadata_check');
        DB::statement('ALTER TABLE mm_supplier_quotes DROP CONSTRAINT mm_supplier_quotes_approval_status_check');

        Schema::table('mm_supplier_quotes', function (Blueprint $table) {
            $table->dropIndex('mm_quote_approval_active_idx');
            $table->dropColumn([
                'approval_status',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
