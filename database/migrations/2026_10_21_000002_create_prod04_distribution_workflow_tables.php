<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prod04_document_sequences', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestampsTz();
        });

        Schema::create('prod04_brief_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_brief_id')->constrained('marketing_briefs')->restrictOnDelete();
            $table->string('document_number', 40);
            $table->unsignedInteger('revision');
            $table->string('readiness_status', 32);
            $table->jsonb('snapshot');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('submitted_at');
            $table->string('distribution_status', 32)->default('PENDING_DELIVERY');
            $table->boolean('delivery_retrying')->default(false);
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampsTz();
            $table->unique(['marketing_brief_id', 'revision']);
            $table->unique(['document_number', 'revision']);
            $table->index(['distribution_status', 'delivery_retrying']);
        });

        Schema::create('prod04_distribution_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role_code', 50);
            $table->string('obligation_type', 20);
            $table->string('delivery_status', 24)->default('PENDING_DELIVERY');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('notified_at')->nullable();
            $table->timestampsTz();
            $table->unique(['brief_version_id', 'user_id']);
            $table->index(['brief_version_id', 'delivery_status']);
        });

        Schema::create('prod04_document_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('obligation_type', 20);
            $table->timestampTz('available_at');
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['brief_version_id', 'user_id']);
        });

        Schema::create('prod04_validation_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('AVAILABLE');
            $table->timestampTz('available_at');
            $table->timestampsTz();
            $table->unique('brief_version_id');
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('prod04_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('acknowledged_by')->constrained('users')->restrictOnDelete();
            $table->string('method', 16);
            $table->timestampTz('acknowledged_at');
            $table->timestampsTz();
            $table->unique('brief_version_id');
        });

        Schema::create('prod04_sla_tracking', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->timestampTz('available_at');
            $table->timestampTz('reminder_sent_at')->nullable();
            $table->timestampTz('overdue_detected_at')->nullable();
            $table->timestampsTz();
            $table->unique('brief_version_id');
        });

        Schema::create('prod04_delivery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('prod04_distribution_recipients')->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('result', 24);
            $table->text('error')->nullable();
            $table->timestampTz('attempted_at');
            $table->unique(['recipient_id', 'attempt_number']);
            $table->index(['brief_version_id', 'attempted_at']);
        });

        Schema::create('prod04_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_brief_id')->constrained('marketing_briefs')->restrictOnDelete();
            $table->foreignId('brief_version_id')->nullable()->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('prod04_distribution_recipients')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('actor_role', 60);
            $table->string('event_type', 60);
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['marketing_brief_id', 'occurred_at']);
            $table->index(['brief_version_id', 'occurred_at']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_prod04_immutable_record_mutation()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF TG_OP = 'UPDATE'
                   AND TG_TABLE_NAME = 'prod04_brief_versions' THEN

                    IF (to_jsonb(NEW) - ARRAY[
                        'delivery_retrying',
                        'distribution_status',
                        'delivered_at',
                        'updated_at'
                    ]) IS DISTINCT FROM
                       (to_jsonb(OLD) - ARRAY[
                        'delivery_retrying',
                        'distribution_status',
                        'delivered_at',
                        'updated_at'
                    ]) THEN
                        RAISE EXCEPTION 'PROD-04 immutable snapshot cannot be modified';
                    END IF;

                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'PROD-04 immutable records are append-only';
            END;
            $$;
        SQL);



        foreach (['prod04_brief_versions', 'prod04_delivery_attempts', 'prod04_events'] as $table) {
            DB::unprepared(sprintf(
                'CREATE TRIGGER %s_append_only BEFORE UPDATE OR DELETE ON %s FOR EACH ROW EXECUTE FUNCTION prevent_prod04_immutable_record_mutation()',
                $table,
                $table,
            ));
            DB::unprepared(sprintf(
                'CREATE TRIGGER %s_no_truncate BEFORE TRUNCATE ON %s FOR EACH STATEMENT EXECUTE FUNCTION prevent_prod04_immutable_record_mutation()',
                $table,
                $table,
            ));
        }
    }

    public function down(): void
    {
        foreach (['prod04_events', 'prod04_delivery_attempts', 'prod04_sla_tracking', 'prod04_acknowledgements', 'prod04_validation_tasks', 'prod04_document_access', 'prod04_distribution_recipients', 'prod04_brief_versions'] as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_no_truncate ON {$table}");
            DB::statement("DROP TRIGGER IF EXISTS {$table}_append_only ON {$table}");
        }

        DB::statement('DROP FUNCTION IF EXISTS prevent_prod04_immutable_record_mutation()');

        foreach (['prod04_events', 'prod04_delivery_attempts', 'prod04_sla_tracking', 'prod04_acknowledgements', 'prod04_validation_tasks', 'prod04_document_access', 'prod04_distribution_recipients', 'prod04_brief_versions', 'prod04_document_sequences'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
