<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mm_units', function (Blueprint $table): void {
            $table->boolean('is_specification_unit')->default(false);
            $table->boolean('is_available_for_new_material')->default(true);
            $table->string('usage_group', 16)->nullable()->default('both')->change();
        });

        DB::table('mm_units')
            ->whereIn('id', DB::table('mm_attribute_definitions')->select('unit_id')->whereNotNull('unit_id'))
            ->update(['is_specification_unit' => true]);
        DB::table('mm_units')
            ->whereIn('id', DB::table('mm_specification_template_attributes')->select('unit_id')->whereNotNull('unit_id'))
            ->update(['is_specification_unit' => true]);

        DB::table('mm_units')
            ->whereRaw('LOWER(BTRIM(name)) IN (?, ?, ?)', ['mm', 'cm', 'inch'])
            ->update([
                'is_specification_unit' => true,
                'is_available_for_new_material' => false,
            ]);

        $specificationOnlyNames = DB::table('mm_units')
            ->whereRaw('LOWER(BTRIM(name)) IN (?, ?, ?)', ['mm', 'cm', 'inch'])
            ->get(['id']);

        foreach ($specificationOnlyNames as $unit) {
            if (! $this->hasMaterialOrPriceReferences((int) $unit->id)) {
                DB::table('mm_units')->where('id', $unit->id)->update(['usage_group' => null]);
            }
        }

        $boxUnits = DB::table('mm_units')
            ->whereRaw('LOWER(BTRIM(name)) = ?', ['box'])
            ->get(['id']);

        foreach ($boxUnits as $unit) {
            if (! $this->hasBaseUnitReferences((int) $unit->id)) {
                DB::table('mm_units')->where('id', $unit->id)->update(['usage_group' => 'price']);
            }
        }

        DB::table('mm_units')
            ->whereRaw('LOWER(BTRIM(name)) IN (?, ?, ?, ?, ?, ?)', ['pcs', 'kg', 'kilogram', 'meter', 'unit', 'set'])
            ->whereNotNull('usage_group')
            ->update(['usage_group' => 'both']);
    }

    public function down(): void
    {
        $hasSpecificationConfiguration = DB::table('mm_units')
            ->whereNull('usage_group')
            ->orWhere('is_specification_unit', true)
            ->orWhere('is_available_for_new_material', false)
            ->exists();

        if ($hasSpecificationConfiguration) {
            throw new RuntimeException('Cannot reverse UOM specification eligibility without losing classification data.');
        }

        Schema::table('mm_units', function (Blueprint $table): void {
            $table->dropColumn(['is_specification_unit', 'is_available_for_new_material']);
            $table->string('usage_group', 16)->default('both')->nullable(false)->change();
        });
    }

    private function hasBaseUnitReferences(int $unitId): bool
    {
        return DB::table('mm_material_variants')->where('base_unit_id', $unitId)->exists()
            || DB::table('mm_material_unit_conversions')->where('base_unit_id', $unitId)->exists();
    }

    private function hasMaterialOrPriceReferences(int $unitId): bool
    {
        return $this->hasBaseUnitReferences($unitId)
            || DB::table('mm_material_prices')->where('price_unit_id', $unitId)->exists()
            || DB::table('mm_supplier_quotes')->where('price_unit_id', $unitId)->exists()
            || DB::table('mm_material_unit_conversions')->where('price_unit_id', $unitId)->exists();
    }
};
