<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_briefs', function (Blueprint $table) {
            $table->text('key_requirements')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('reference_inspiration')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_briefs', function (Blueprint $table) {
            $table->dropColumn([
                'key_requirements',
                'target_audience',
                'reference_inspiration',
            ]);
        });
    }
};
