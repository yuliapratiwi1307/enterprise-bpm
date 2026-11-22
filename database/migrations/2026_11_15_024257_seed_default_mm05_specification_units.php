<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $specificationUnits = [
            'mm' => 'length',
            'cm' => 'length',
            'inch' => 'length',
            'km' => 'length',
            'micrometer' => 'length',
            'm²' => 'area',
            'm³' => 'volume',
            'derajat Celsius' => 'temperature',
            'Volt' => 'voltage',
            'Watt' => 'power',
            'Ampere' => 'current',
        ];

        foreach ($specificationUnits as $name => $dimension) {
            $existingUnitIds = DB::table('mm_units')
                ->whereRaw('LOWER(BTRIM(name)) = ?', [mb_strtolower($name)])
                ->pluck('id');

            if ($existingUnitIds->isNotEmpty()) {
                DB::table('mm_units')
                    ->whereIn('id', $existingUnitIds)
                    ->update([
                        'is_specification_unit' => true,
                        'is_available_for_new_material' => false,
                    ]);

                continue;
            }

            DB::table('mm_units')->insert([
                'code' => $this->nextUnitCode(),
                'name' => $name,
                'dimension' => $dimension,
                'usage_group' => null,
                'is_specification_unit' => true,
                'is_available_for_new_material' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Default specification UOM records are retained to protect master data and references.');
    }

    private function nextUnitCode(): string
    {
        $highestSequence = 0;

        foreach (DB::table('mm_units')->pluck('code') as $code) {
            if (preg_match('/\AUOM-(\d+)\z/', (string) $code, $matches) === 1) {
                $highestSequence = max($highestSequence, (int) $matches[1]);
            }
        }

        return 'UOM-'.str_pad((string) ($highestSequence + 1), 3, '0', STR_PAD_LEFT);
    }
};
