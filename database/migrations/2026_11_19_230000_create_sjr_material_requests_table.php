<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sjr_material_requests')) return;
        Schema::create('sjr_material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 48)->nullable()->unique();
            $table->unsignedBigInteger('handoff_id')->index();
            $table->unsignedBigInteger('sjr_job_id')->index();
            $table->unsignedBigInteger('sjr_job_item_id')->index();
            $table->unsignedBigInteger('technical_draft_id')->nullable()->index();
            $table->unsignedInteger('material_index');
            $table->string('material_name', 180);
            $table->json('material_payload_json');
            $table->string('status', 40)->default('SUBMITTED')->index();
            $table->unsignedBigInteger('submitted_by')->nullable()->index();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['handoff_id', 'sjr_job_item_id', 'material_index'], 'sjr_mat_req_item_idx_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('sjr_material_requests'); }
};
