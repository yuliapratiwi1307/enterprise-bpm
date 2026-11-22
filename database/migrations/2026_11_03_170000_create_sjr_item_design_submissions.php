<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function guard(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || (! (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')) && ! (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test'))) {
            throw new RuntimeException('SJR V5.5 migration hanya boleh berjalan di enterprise_bpm_browser.');
        }
    }

    public function up(): void
    {
        $this->guard();
        Schema::create('sjr_item_design_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sjr_job_item_id')->constrained('sjr_job_items')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('state', 25)->default('DRAFT');
            $table->foreignId('designer_user_id')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['sjr_job_item_id', 'version'], 'sjr_item_design_item_version_unique');
            $table->index(['sjr_job_item_id', 'state'], 'sjr_item_design_status_idx');
        });
        Schema::create('sjr_item_design_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('sjr_item_design_submissions')->cascadeOnDelete();
            $table->string('original_name', 250);
            $table->string('storage_path')->unique();
            $table->string('mime_type', 125);
            $table->unsignedBigInteger('byte_size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->guard();
        Schema::dropIfExists('sjr_item_design_files');
        Schema::dropIfExists('sjr_item_design_submissions');
    }
};
