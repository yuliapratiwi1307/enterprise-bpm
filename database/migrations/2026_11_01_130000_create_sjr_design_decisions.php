<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'pgsql' || (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test')) && ! (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: DB pengujian saja');
        }
        Schema::create('sjr_design_decisions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('sjr_job_id');
            $t->foreign('sjr_job_id')->references('id')->on('sjr_jobs');
            $t->unsignedBigInteger('submission_id')->unique();
            $t->foreign('submission_id')->references('id')->on('sjr_design_submissions');
            $t->unsignedInteger('submission_version');
            $t->unsignedBigInteger('marketing_user_id');
            $t->string('action', 32);
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        if (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: DB pengujian saja');
        }
        Schema::dropIfExists('sjr_design_decisions');
    }
};
