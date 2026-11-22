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
            throw new RuntimeException('Migrasi review item SJR hanya untuk enterprise_bpm_browser.');
        }
    }

    public function up(): void
    {
        $this->guard();
        Schema::create('sjr_item_design_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sjr_job_item_id')->constrained('sjr_job_items')->cascadeOnDelete();
            $table->foreignId('submission_id')->unique()->constrained('sjr_item_design_submissions')->cascadeOnDelete();
            $table->string('decision', 25);
            $table->foreignId('reviewer_user_id')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['sjr_job_item_id', 'created_at'], 'sjr_item_review_history_idx');
        });
    }

    public function down(): void
    {
        $this->guard();
        Schema::dropIfExists('sjr_item_design_reviews');
    }
};
