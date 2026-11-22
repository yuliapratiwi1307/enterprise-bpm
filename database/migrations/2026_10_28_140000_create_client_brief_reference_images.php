<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_brief_reference_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_brief_id')->constrained('client_briefs')->restrictOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['client_brief_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_brief_reference_images');
    }
};
