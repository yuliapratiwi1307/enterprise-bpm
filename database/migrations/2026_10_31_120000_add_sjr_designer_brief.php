<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sjr_jobs')) {
            throw new RuntimeException('Tabel sjr_jobs tidak ada');
        }
        Schema::table('sjr_jobs', function (Blueprint $table) {
            $table->text('designer_brief')->nullable();
            $table->date('designer_deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sjr_jobs', fn (Blueprint $table) => $table->dropColumn(['designer_brief', 'designer_deadline']));
    }
};
