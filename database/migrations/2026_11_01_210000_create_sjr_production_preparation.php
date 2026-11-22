<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'pgsql' || (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test')) && ! (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: Hanya database pengujian SJR');
        }
        Schema::create('sjr_production_brief_reviews', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sjr_job_id')->unique();
            $t->foreign('sjr_job_id')->references('id')->on('sjr_jobs');
            $t->string('decision', 24);
            $t->text('note')->nullable();
            $t->unsignedBigInteger('reviewed_by');
            $t->timestamp('reviewed_at');
            $t->timestamps();
        });
        Schema::create('sjr_technical_drafts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sjr_job_id')->unique();
            $t->foreign('sjr_job_id')->references('id')->on('sjr_jobs');
            $t->jsonb('materials_json');
            $t->jsonb('processes_json');
            $t->text('instructions')->nullable();
            $t->unsignedInteger('leadtime_days')->nullable();
            $t->text('leadtime_note')->nullable();
            $t->string('state', 24)->default('DRAFT');
            $t->unsignedBigInteger('saved_by');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        if (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP');
        }Schema::dropIfExists('sjr_technical_drafts');
        Schema::dropIfExists('sjr_production_brief_reviews');
    }
};
