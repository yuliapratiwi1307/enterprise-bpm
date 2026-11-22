<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('bpm_roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('bpm_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('bpm_role_id')->constrained('bpm_roles')->restrictOnDelete();
            $table->unique(['user_id', 'bpm_role_id']);
        });
        Schema::create('client_briefs', function (Blueprint $table) {
            $table->id(); // ID internal Draft
            $table->string('request_number', 40)->nullable()->unique();
            $table->string('status', 20)->default('DRAFT')->index();
            $table->string('client_name')->nullable();
            $table->string('communication_source', 100)->nullable();
            $table->string('requirement_type', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('client_pic')->nullable();
            $table->date('target_date')->nullable();
            $table->string('work_location')->nullable();
            $table->foreignId('marketing_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('primary_brief_id')->nullable()->constrained('client_briefs')->restrictOnDelete();
            $table->text('duplicate_reason')->nullable();
            $table->timestampTz('recorded_at')->nullable();
            $table->timestampTz('stage_3_started_at')->nullable();
            $table->timestampsTz();
            $table->index(['client_name','requirement_type']);
        });
        Schema::create('client_brief_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_brief_id')->constrained('client_briefs')->restrictOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
        });
        Schema::create('client_brief_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_brief_id')->constrained('client_briefs')->restrictOnDelete();
            $table->string('event_type', 60);
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_role', 60);
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['client_brief_id','occurred_at']);
        });
        Schema::create('request_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestampsTz();
        });
    }
    public function down(): void
    {
        foreach (['request_number_sequences','client_brief_events','client_brief_attachments','client_briefs','bpm_role_user','bpm_roles','departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
