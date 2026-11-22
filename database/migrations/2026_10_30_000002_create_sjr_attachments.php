<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'pgsql' || (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test')) && ! (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: hanya database pengujian SJR');
        }
        Schema::create('sjr_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sjr_job_id')->index();
            $table->foreign('sjr_job_id')->references('id')->on('sjr_jobs')->cascadeOnDelete();
            $table->string('original_name', 250);
            $table->string('storage_path', 512)->unique();
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('byte_size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP');
        }Schema::dropIfExists('sjr_attachments');
    }
};
