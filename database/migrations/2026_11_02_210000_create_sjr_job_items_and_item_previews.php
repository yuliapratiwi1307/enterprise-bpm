<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function assertTestDatabase(): void
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        if ($connection->getDriverName() !== 'pgsql' || (! ($database === 'enterprise_bpm_browser' || (app()->environment('testing') && $database === 'enterprise_bpm_test')) && ! (app()->environment('testing') && $database === 'enterprise_bpm_test'))) {
            throw new RuntimeException('SJR V5.2 migration is permitted ONLY on PostgreSQL database enterprise_bpm_browser. Actual: '.$connection->getDriverName().'/'.$database);
        }
    }

    public function up(): void
    {
        $this->assertTestDatabase();

        Schema::create('sjr_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sjr_job_id')->constrained('sjr_jobs')->cascadeOnDelete();
            $table->unsignedInteger('item_index');
            $table->string('item_name');
            $table->string('dimensions', 150);
            $table->string('dimension_unit', 20)->default('cm');
            $table->unsignedInteger('quantity');
            $table->boolean('requires_designer')->default(false);
            $table->text('designer_brief')->nullable();
            $table->date('designer_deadline')->nullable();
            // NOT_REQUIRED, PENDING, IN_REVIEW, REVISION, APPROVED;
            // application layer validates transitions and project handover.
            $table->string('design_state', 30)->default('NOT_REQUIRED');
            $table->timestamps();
            $table->unique(['sjr_job_id', 'item_index'], 'sjr_job_items_job_index_unique');
            $table->index(['sjr_job_id', 'requires_designer'], 'sjr_job_items_designer_lookup');
        });

        Schema::create('sjr_item_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sjr_job_item_id')->constrained('sjr_job_items')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('storage_path')->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('byte_size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['sjr_job_item_id', 'sort_order'], 'sjr_item_previews_order_index');
        });
    }

    public function down(): void
    {
        $this->assertTestDatabase();
        Schema::dropIfExists('sjr_item_previews');
        Schema::dropIfExists('sjr_job_items');
    }
};
