<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mm_specification_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('category_id')->constrained('mm_categories')->restrictOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category_id', 'is_active'], 'mm_template_category_active_idx');
        });

        Schema::create('mm_specification_template_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('template_id')->constrained('mm_specification_templates')->restrictOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('mm_attribute_definitions')->restrictOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('input_type', 32)->default('text');
            $table->foreignId('unit_id')->nullable()->constrained('mm_units')->restrictOnDelete();
            $table->json('option_values')->nullable();
            $table->timestamps();
            $table->unique(['template_id', 'attribute_definition_id'], 'mm_template_attr_unique');
            $table->index(['template_id', 'display_order'], 'mm_template_attr_order_idx');
        });

        Schema::table('mm_materials', function (Blueprint $table) {
            $table->foreignId('specification_template_id')->nullable()->after('category_id')->constrained('mm_specification_templates')->restrictOnDelete();
            $table->index(['specification_template_id', 'category_id'], 'mm_material_template_idx');
        });

        DB::statement('ALTER TABLE mm_materials ADD CONSTRAINT mm_material_template_category_match CHECK (specification_template_id IS NULL OR category_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::table('mm_materials', function (Blueprint $table) {
            $table->dropIndex('mm_material_template_idx');
            $table->dropForeign(['specification_template_id']);
            $table->dropColumn('specification_template_id');
        });

        Schema::dropIfExists('mm_specification_template_attributes');
        Schema::dropIfExists('mm_specification_templates');
    }
};
