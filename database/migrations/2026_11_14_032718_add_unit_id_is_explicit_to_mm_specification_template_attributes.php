<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mm_specification_template_attributes', function (Blueprint $table) {
            $table->boolean('unit_id_is_explicit')->default(false);
        });

        DB::table('mm_specification_template_attributes')
            ->whereNotNull('unit_id')
            ->update(['unit_id_is_explicit' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mm_specification_template_attributes', function (Blueprint $table) {
            $table->dropColumn('unit_id_is_explicit');
        });
    }
};
