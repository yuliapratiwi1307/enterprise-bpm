<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add mm_material_variant_id reference to sjr_multi_item_technical_drafts table.
     * This allows each Multi-Item item to optionally link to an MM-05 Material Variant.
     * 
     * SAFETY NOTES:
     * - This migration adds a nullable foreign key column to existing table.
     * - It does NOT alter sjr_jobs, sjr_multi_item_handoffs, or base tables.
     * - For backward compatibility, the column is nullable; items without MM-05 linkage remain functional.
     * - The foreign key uses ON DELETE CASCADE to handle variant deactivation safely.
     */
    public function up(): void
    {
        Schema::table('sjr_multi_item_technical_drafts', function (Blueprint $table) {
            // Add foreign key reference to mm_material_variants
            $table->unsignedBigInteger('mm_material_variant_id')->nullable()->after('id');
            
            // Create index for performance
            $table->index(['mm_material_variant_id'], 'sjr_multi_item_tech_draft_mm_material_idx');
            
            // Add foreign key constraint
            $table->foreign('mm_material_variant_id')
                ->references('id')
                ->on('mm_material_variants')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('sjr_multi_item_technical_drafts', function (Blueprint $table) {
            $table->dropForeign(['mm_material_variant_id']);
            $table->dropIndex('sjr_multi_item_tech_draft_mm_material_idx');
            $table->dropColumn('mm_material_variant_id');
        });
    }
};
