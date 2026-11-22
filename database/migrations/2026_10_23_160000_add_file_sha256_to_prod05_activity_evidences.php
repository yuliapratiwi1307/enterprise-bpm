<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prod05_activity_evidences', function (Blueprint $table): void {
            $table->char('file_sha256', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prod05_activity_evidences', function (Blueprint $table): void {
            $table->dropColumn('file_sha256');
        });
    }
};
