<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sjr_multi_item_handoffs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sjr_job_id')->unique();
            $table->unsignedBigInteger('sent_by');
            $table->jsonb('item_snapshot_json');
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->foreign('sjr_job_id')->references('id')->on('sjr_jobs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sjr_multi_item_handoffs');
    }
};
