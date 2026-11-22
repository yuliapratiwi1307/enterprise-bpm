<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'pgsql' || (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test')) && ! (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: Hanya DB pengujian SJR');
        }
        Schema::create('sjr_design_submissions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('sjr_job_id');
            $t->foreign('sjr_job_id')->references('id')->on('sjr_jobs');
            $t->unsignedInteger('version');
            $t->unsignedBigInteger('designer_user_id');
            $t->text('note');
            $t->timestamps();
            $t->unique(['sjr_job_id', 'version']);
        });
        Schema::create('sjr_design_files', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('submission_id');
            $t->foreign('submission_id')->references('id')->on('sjr_design_submissions');
            $t->string('original_name', 250);
            $t->string('storage_path', 512)->unique();
            $t->unsignedBigInteger('byte_size');
            $t->string('mime_type', 150);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        if (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP');
        }
        Schema::dropIfExists('sjr_design_files');
        Schema::dropIfExists('sjr_design_submissions');
    }
};
