<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prod05_activity_evidences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('validation_id')
                ->constrained('prod05_validations')
                ->restrictOnDelete();

            $table->foreignId('activity_control_id')
                ->constrained('prod05_activity_controls')
                ->restrictOnDelete();

            $table->unsignedInteger('brief_revision');
            $table->string('evidence_type', 60);
            $table->string('document_reference', 255);
            $table->text('description');

            $table->string('verification_status', 32)
                ->default('PENDING');

            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestampTz('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestampsTz();

            $table->index([
                'activity_control_id',
                'brief_revision',
                'verification_status',
            ], 'prod05_evidence_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prod05_activity_evidences');
    }
};
