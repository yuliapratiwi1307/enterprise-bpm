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
        Schema::table('mm_units', function (Blueprint $table) {
            $table->string('usage_group', 16)->default('both');
            $table->index(['is_active', 'usage_group', 'name'], 'mm_units_usage_lookup_idx');
        });

        DB::statement(
            "ALTER TABLE mm_units ADD CONSTRAINT mm_units_usage_group_check CHECK (usage_group IN ('base', 'price', 'both'))"
        );

        Schema::create('mm_material_unit_conversions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('material_id')->constrained('mm_materials')->restrictOnDelete();
            $table->foreignId('base_unit_id')->constrained('mm_units')->restrictOnDelete();
            $table->foreignId('price_unit_id')->constrained('mm_units')->restrictOnDelete();
            $table->decimal('conversion_factor', 20, 8);
            $table->timestamps();
            $table->unique(
                ['material_id', 'base_unit_id', 'price_unit_id'],
                'mm_material_unit_conversion_unique',
            );
            $table->index('base_unit_id', 'mm_material_unit_conversion_base_idx');
            $table->index('price_unit_id', 'mm_material_unit_conversion_price_idx');
        });

        DB::statement(
            'ALTER TABLE mm_material_unit_conversions ADD CONSTRAINT mm_material_unit_conversion_factor_positive CHECK (conversion_factor > 0)'
        );

        Schema::table('mm_material_prices', function (Blueprint $table) {
            $table->decimal('conversion_factor', 20, 8)->nullable();
        });

        DB::statement(
            'ALTER TABLE mm_material_prices ADD CONSTRAINT mm_material_prices_conversion_factor_positive CHECK (conversion_factor IS NULL OR conversion_factor > 0)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'ALTER TABLE mm_material_prices DROP CONSTRAINT IF EXISTS mm_material_prices_conversion_factor_positive'
        );
        Schema::table('mm_material_prices', function (Blueprint $table) {
            $table->dropColumn('conversion_factor');
        });

        DB::statement(
            'ALTER TABLE mm_material_unit_conversions DROP CONSTRAINT IF EXISTS mm_material_unit_conversion_factor_positive'
        );
        Schema::dropIfExists('mm_material_unit_conversions');

        DB::statement('ALTER TABLE mm_units DROP CONSTRAINT IF EXISTS mm_units_usage_group_check');
        Schema::table('mm_units', function (Blueprint $table) {
            $table->dropIndex('mm_units_usage_lookup_idx');
            $table->dropColumn('usage_group');
        });
    }
};
