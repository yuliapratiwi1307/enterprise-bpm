<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_brief_attachments', function (Blueprint $table) {
            $table->string('category', 100)->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_brief_attachments', function (Blueprint $table) {
            $table->dropColumn(['category', 'notes']);
        });
    }
};
