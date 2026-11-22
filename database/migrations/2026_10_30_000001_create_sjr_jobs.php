<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'pgsql' || (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test')) && ! (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: SJR migration hanya untuk enterprise_bpm_browser');
        }
        Schema::create('sjr_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job_number', 50)->unique();
            $table->unsignedBigInteger('marketing_user_id')->index();
            $table->string('client_name', 200);
            $table->string('job_title', 250);
            $table->string('client_pic', 200)->nullable();
            $table->date('target_date');
            $table->text('brief_details');
            $table->string('quantity', 100);
            $table->text('notes')->nullable();
            $table->boolean('requires_designer');
            $table->string('status', 40)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: DB tidak sesuai');
        }Schema::dropIfExists('sjr_jobs');
    }
};
