<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mm_material_prices', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('material_variant_id')
                ->constrained('mm_suppliers')->restrictOnDelete();
            $table->index(['material_variant_id', 'supplier_id', 'effective_from'], 'mm_price_supplier_history_idx');
        });

        // Preserve supplier identity for existing price-history rows created from quotes.
        DB::statement(<<<'SQL'
            UPDATE mm_material_prices AS prices
            SET supplier_id = quotes.supplier_id
            FROM mm_supplier_quotes AS quotes
            WHERE prices.source_quote_id = quotes.id
              AND prices.supplier_id IS NULL
            SQL);
    }

    public function down(): void
    {
        Schema::table('mm_material_prices', function (Blueprint $table) {
            $table->dropIndex('mm_price_supplier_history_idx');
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
