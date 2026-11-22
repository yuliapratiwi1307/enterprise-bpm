<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_brief_id')->constrained('client_briefs')->restrictOnDelete();
            $table->foreignId('marketing_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('DRAFT')->index();
            $table->jsonb('readiness_snapshot')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampsTz();
            $table->index(['client_brief_id', 'status']);
        });

        Schema::create('marketing_brief_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_brief_id')->constrained('marketing_briefs')->restrictOnDelete();
            $table->string('component', 40);
            $table->text('value')->nullable();
            $table->boolean('is_provisional')->default(false);
            $table->text('information_basis')->nullable();
            $table->timestampsTz();
            $table->unique(['marketing_brief_id', 'component']);
        });

        Schema::create('design_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_brief_id')->constrained('marketing_briefs')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->string('recommended_classification', 20)->nullable();
            $table->string('classification', 20)->nullable();
            $table->text('classification_reason')->nullable();
            $table->text('impact')->nullable();
            $table->string('status', 32)->default('AVAILABLE')->index();
            $table->foreignId('current_pic_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('deadline_at')->nullable()->index();
            $table->timestampTz('deadline_reminder_sent_at')->nullable();
            $table->timestampTz('overdue_notified_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('replacement_information')->nullable();
            $table->string('replacement_source', 255)->nullable();
            $table->timestampTz('cancellation_requested_at')->nullable();
            $table->timestampTz('cancellation_acknowledged_at')->nullable();
            $table->timestampTz('cancellation_reminder_sent_at')->nullable();
            $table->timestampsTz();
            $table->index(['marketing_brief_id', 'status']);
        });

        Schema::create('design_request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained('design_requests')->restrictOnDelete();
            $table->foreignId('designer_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('assigned_at');
            $table->timestampTz('released_at')->nullable();
            $table->text('release_reason')->nullable();
            $table->timestampsTz();
            $table->index(['design_request_id', 'released_at']);
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX design_request_one_active_assignment
            ON design_request_assignments (design_request_id)
            WHERE released_at IS NULL
        SQL);

        Schema::create('design_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained('design_requests')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->timestampTz('created_at');
            $table->unique(['design_request_id', 'version']);
        });

        Schema::create('design_request_deadline_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained('design_requests')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('previous_deadline_at')->nullable();
            $table->timestampTz('requested_deadline_at');
            $table->text('request_reason');
            $table->string('status', 20)->default('PENDING');
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestampsTz();
            $table->index(['design_request_id', 'status']);
        });

        Schema::create('production_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_brief_id')->constrained('marketing_briefs')->restrictOnDelete();
            $table->foreignId('design_request_id')->nullable()->constrained('design_requests')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_role', 60);
            $table->string('event_type', 60)->index();
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedInteger('attachment_version')->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->index(['marketing_brief_id', 'occurred_at']);
            $table->index(['design_request_id', 'occurred_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'created_at']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_production_workflow_event_mutation()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION 'Production workflow events are append-only';
            END;
            $$
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER production_workflow_events_append_only
            BEFORE UPDATE OR DELETE ON production_workflow_events
            FOR EACH ROW EXECUTE FUNCTION prevent_production_workflow_event_mutation()
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER production_workflow_events_no_truncate
            BEFORE TRUNCATE ON production_workflow_events
            FOR EACH STATEMENT EXECUTE FUNCTION prevent_production_workflow_event_mutation()
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS production_workflow_events_no_truncate ON production_workflow_events');
        DB::statement('DROP TRIGGER IF EXISTS production_workflow_events_append_only ON production_workflow_events');
        DB::statement('DROP FUNCTION IF EXISTS prevent_production_workflow_event_mutation()');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('production_workflow_events');
        Schema::dropIfExists('design_request_deadline_changes');
        Schema::dropIfExists('design_request_attachments');
        DB::statement('DROP INDEX IF EXISTS design_request_one_active_assignment');
        Schema::dropIfExists('design_request_assignments');
        Schema::dropIfExists('design_requests');
        Schema::dropIfExists('marketing_brief_components');
        Schema::dropIfExists('marketing_briefs');
    }
};
