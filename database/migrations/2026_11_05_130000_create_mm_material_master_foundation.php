<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MM-01 storage only. Does not modify SJR, PROD, HPP, or inventory tables.
        Schema::create('mm_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('name', 100);
            $table->string('dimension', 32)->nullable(); // count, length, area, volume, mass
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mm_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->foreignId('parent_id')->nullable()->constrained('mm_categories')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mm_materials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('category_id')->constrained('mm_categories')->restrictOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable(); // audit user ID; no user table assumption
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'name']);
        });

        Schema::create('mm_attribute_definitions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('category_id')->constrained('mm_categories')->restrictOnDelete();
            $table->string('code', 60);
            $table->string('label', 150);
            $table->string('data_type', 20)->default('text'); // text, number, boolean
            $table->foreignId('unit_id')->nullable()->constrained('mm_units')->restrictOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['category_id', 'code'], 'mm_attr_category_code_unique');
        });

        Schema::create('mm_material_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('material_id')->constrained('mm_materials')->restrictOnDelete();
            $table->string('sku', 90)->unique();
            $table->string('variant_name', 255);
            $table->foreignId('base_unit_id')->constrained('mm_units')->restrictOnDelete();
            $table->text('specification_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['material_id', 'is_active']);
        });

        Schema::create('mm_variant_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('variant_id')->constrained('mm_material_variants')->restrictOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('mm_attribute_definitions')->restrictOnDelete();
            $table->string('value_text', 500)->nullable();
            $table->decimal('value_number', 18, 6)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();
            $table->unique(['variant_id', 'attribute_definition_id'], 'mm_var_attr_unique');
        });

        Schema::create('mm_suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 60)->unique();
            $table->string('name', 255);
            $table->string('contact_name', 150)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mm_supplier_quotes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('material_variant_id')->constrained('mm_material_variants')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('mm_suppliers')->restrictOnDelete();
            $table->foreignId('price_unit_id')->constrained('mm_units')->restrictOnDelete();
            $table->decimal('amount', 20, 4);
            $table->char('currency', 3)->default('IDR');
            $table->date('quoted_on');
            $table->date('valid_until')->nullable();
            $table->string('supplier_reference', 120)->nullable();
            $table->string('source_document_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->index(['material_variant_id', 'quoted_on'], 'mm_quote_variant_date_idx');
            $table->index(['supplier_id', 'quoted_on'], 'mm_quote_supplier_date_idx');
        });

        Schema::create('mm_material_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('material_variant_id')->constrained('mm_material_variants')->restrictOnDelete();
            $table->foreignId('price_unit_id')->constrained('mm_units')->restrictOnDelete();
            $table->foreignId('source_quote_id')->nullable()->constrained('mm_supplier_quotes')->restrictOnDelete();
            $table->string('price_type', 32)->default('reference');
            $table->decimal('amount', 20, 4);
            $table->char('currency', 3)->default('IDR');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('source_name', 255)->nullable();
            $table->text('change_reason')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->index(['material_variant_id', 'price_type', 'effective_from'], 'mm_price_lookup_idx');
        });

        // MM-01 PostgreSQL integrity checks: pricing and date ranges.
        DB::statement(
            'ALTER TABLE mm_supplier_quotes ADD CONSTRAINT mm_supplier_quotes_amount_positive CHECK (amount > 0)'
        );
        DB::statement(
            'ALTER TABLE mm_supplier_quotes ADD CONSTRAINT mm_supplier_quotes_valid_dates CHECK (valid_until IS NULL OR valid_until >= quoted_on)'
        );
        DB::statement(
            'ALTER TABLE mm_material_prices ADD CONSTRAINT mm_material_prices_amount_positive CHECK (amount > 0)'
        );
        DB::statement(
            'ALTER TABLE mm_material_prices ADD CONSTRAINT mm_material_prices_valid_dates CHECK (effective_until IS NULL OR effective_until >= effective_from)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('mm_material_prices');
        Schema::dropIfExists('mm_supplier_quotes');
        Schema::dropIfExists('mm_suppliers');
        Schema::dropIfExists('mm_variant_attributes');
        Schema::dropIfExists('mm_material_variants');
        Schema::dropIfExists('mm_attribute_definitions');
        Schema::dropIfExists('mm_materials');
        Schema::dropIfExists('mm_categories');
        Schema::dropIfExists('mm_units');
    }
};
