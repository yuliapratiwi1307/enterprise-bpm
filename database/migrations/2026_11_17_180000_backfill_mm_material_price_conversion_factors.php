<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill only conversion factors that can be determined unambiguously
     * from the material's base unit or an existing MM-05 conversion record.
     */
    public function up(): void
    {
        // Same price unit and base unit: one price unit is exactly one base unit.
        DB::statement(<<<'SQL'
            UPDATE mm_material_prices AS prices
            SET conversion_factor = 1
            FROM mm_material_variants AS variants
            WHERE prices.material_variant_id = variants.id
              AND prices.price_unit_id = variants.base_unit_id
              AND prices.conversion_factor IS NULL
        SQL);

        // Other units: use the material-specific conversion already maintained in MM-05.
        DB::statement(<<<'SQL'
            UPDATE mm_material_prices AS prices
            SET conversion_factor = conversions.conversion_factor
            FROM mm_material_variants AS variants
            INNER JOIN mm_material_unit_conversions AS conversions
                ON conversions.material_id = variants.material_id
               AND conversions.base_unit_id = variants.base_unit_id
            WHERE prices.material_variant_id = variants.id
              AND prices.price_unit_id = conversions.price_unit_id
              AND prices.conversion_factor IS NULL
        SQL);
    }

    /**
     * Deliberately retain backfilled snapshots on rollback: deleting historical
     * conversion data would make previously recorded prices less interpretable.
     */
    public function down(): void
    {
        // No-op by design.
    }
};
