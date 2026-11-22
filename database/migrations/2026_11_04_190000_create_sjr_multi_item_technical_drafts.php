<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sjr_multi_item_technical_drafts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('handoff_id');
            $table->unsignedBigInteger('sjr_job_item_id');
            $table->json('materials_json');
            $table->json('processes_json');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('leadtime_days')->nullable();
            $table->text('leadtime_note')->nullable();
            $table->unsignedBigInteger('saved_by');
            $table->timestamp('saved_at');
            $table->timestamps();
            $table->unique(['handoff_id', 'sjr_job_item_id'], 'sjr_tech_draft_unique_item');
            $table->foreign('handoff_id')->references('id')->on('sjr_multi_item_handoffs')->cascadeOnDelete();
            $table->foreign('sjr_job_item_id')->references('id')->on('sjr_job_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sjr_multi_item_technical_drafts');
    }
};
