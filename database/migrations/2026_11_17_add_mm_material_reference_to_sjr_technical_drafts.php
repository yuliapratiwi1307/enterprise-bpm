<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add mm_material_variant_id reference to sjr_technical_drafts table.
     * This allows linking technical drafts to MM-05 Material Master without breaking legacy JSON structure.
     * 
     * SAFETY NOTES:
     * - This migration adds a nullable column to existing table.
     * - It does NOT alter sjr_jobs, sjr_multi_item_handoffs, or sjr_multi_item_technical_drafts (Multi-Item).
     * - For Multi-Item items that reference MM-05, we will add a separate mm_material_variant_id column.
     * - Data integrity: The column is nullable, so existing drafts without MM-05 linkage remain fully functional.
     */
    public function up(): void
    {
        Schema::table('sjr_technical_drafts', function (Blueprint $table) {
            // Add foreign key reference to mm_material_variants
            $table->unsignedBigInteger('mm_material_variant_id')->nullable()->after('id');
            
            // Create index for performance
            $table->index(['mm_material_variant_id'], 'sjr_tech_draft_mm_material_idx');
            
            // Add foreign key constraint
            $table->foreign('mm_material_variant_id')
                ->references('id')
                ->on('mm_material_variants')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('sjr_technical_drafts', function (Blueprint $table) {
            $table->dropForeign(['mm_material_variant_id']);
            $table->dropIndex('sjr_tech_draft_mm_material_idx');
            $table->dropColumn('mm_material_variant_id');
        });
    }
};
