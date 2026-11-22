<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prod05_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brief_version_id')->constrained('prod04_brief_versions')->restrictOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('DRAFT');
            $table->string('impact_classification')->nullable();
            $table->string('impact_classification_confirmed')->nullable();
            $table->string('manager_decision')->nullable();
            $table->timestampTz('manager_decision_at')->nullable();
            $table->jsonb('provisional_data')->nullable();
            $table->jsonb('on_hold_scope')->nullable();
            $table->timestampTz('hold_released_at')->nullable();
            $table->foreignId('hold_released_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision_from')->nullable();
            $table->unsignedInteger('revision_to')->nullable();
            $table->text('return_reason')->nullable();
            $table->text('return_field')->nullable();
            $table->timestampsTz();
            $table->unique('brief_version_id');
            $table->index(['status', 'impact_classification']);
        });

        Schema::create('prod05_validation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('validation_id')->constrained('prod05_validations')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('actor_role', 60);
            $table->string('event_type', 60);
            $table->string('before_status', 32)->nullable();
            $table->string('after_status', 32)->nullable();
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['validation_id', 'occurred_at']);
        });

        Schema::create('prod05_revision_classifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('validation_id')->constrained('prod05_validations')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignId('recommended_by')->constrained('users')->restrictOnDelete();
            $table->string('recommended_classification', 32);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('confirmed_classification', 32)->nullable();
            $table->boolean('manager_approval_for_downgrade')->default(false);
            $table->text('confirmation_reason')->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['validation_id', 'revision']);
        });

        Schema::create('prod05_activity_controls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('validation_id')->constrained('prod05_validations')->restrictOnDelete();
            $table->string('activity_key', 100);
            $table->string('activity_type', 32);
            $table->string('control_status', 32)->default('ACTIVE');
            $table->string('hold_reason')->nullable();
            $table->jsonb('downstream_dependencies')->nullable();
            $table->foreignId('affected_by_validation_id')->nullable()->constrained('prod05_validations')->restrictOnDelete();
            $table->timestampTz('hold_requested_at')->nullable();
            $table->timestampTz('hold_released_at')->nullable();
            $table->timestampsTz();
            $table->unique(['validation_id', 'activity_key']);
            $table->index(['control_status', 'hold_reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prod05_activity_controls');
        Schema::dropIfExists('prod05_revision_classifications');
        Schema::dropIfExists('prod05_validation_events');
        Schema::dropIfExists('prod05_validations');
    }
};
