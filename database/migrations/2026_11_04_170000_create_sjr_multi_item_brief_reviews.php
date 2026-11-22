<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sjr_multi_item_brief_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('handoff_id')->unique();
            $table->string('decision', 20);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('reviewed_by');
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->foreign('handoff_id')->references('id')->on('sjr_multi_item_handoffs')->cascadeOnDelete();
        });
        Schema::create('sjr_multi_item_brief_review_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('handoff_id');
            $table->string('decision', 20);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('reviewed_by');
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->foreign('handoff_id')->references('id')->on('sjr_multi_item_handoffs')->cascadeOnDelete();
            $table->index(['handoff_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sjr_multi_item_brief_review_events');
        Schema::dropIfExists('sjr_multi_item_brief_reviews');
    }
};
