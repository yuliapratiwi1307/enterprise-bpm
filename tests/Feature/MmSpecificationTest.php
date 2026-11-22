<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialPrice;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmSupplierQuote;
use App\Models\MmUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MmSpecificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_specification_maintenance_is_scoped_and_deactivation_preserves_saved_values(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $otherDefinition = $this->createDefinition($otherCategory, 'text', 'Other Category Marker');
        $categoryId = (int) $catalog['category']->id;
        $indexRoute = route('mm05.categories.specifications.index', ['category' => $categoryId]);

        $newCategoryName = 'Procurement Test Category '.$this->suffix();
        $this->actingAs($procurement);
        $this->post(route('mm05.categories.store'), ['name' => $newCategoryName])
            ->assertRedirect(route('mm05.categories.index'));
        $createdCategory = MmCategory::query()->where('name', $newCategoryName)->sole();
        $this->assertMatchesRegularExpression('/^CAT-\d{3,}$/', $createdCategory->code);

        $this->get(route('mm05.categories.index'))
            ->assertOk()
            ->assertSee('Kelola Spesifikasi')
            ->assertSee($indexRoute)
            ->assertDontSee('>Spesifikasi</a>', false);

        $this->get($indexRoute)
            ->assertOk()
            ->assertSee($catalog['category']->name)
            ->assertDontSee($otherDefinition->label)
            ->assertSee('Nama atribut')
            ->assertSee('Tipe data')
            ->assertSee('Ketentuan')
            ->assertSee('Tambah Atribut')
            ->assertSee('class="mm-spec-empty"', false)
            ->assertDontSee('<table', false);

        $this->post(route('mm05.categories.specifications.store', ['category' => $categoryId]), [
            'label' => 'Factory Grade',
            'data_type' => 'text',
        ])->assertRedirect($indexRoute);

        $definition = MmAttributeDefinition::query()
            ->where('category_id', $categoryId)
            ->where('label', 'Factory Grade')
            ->sole();
        $this->get($indexRoute)
            ->assertOk()
            ->assertSee('class="mm-spec-card"', false)
            ->assertSee('Opsional');
        $this->assertStringStartsWith('CUSTOM_', $definition->code);
        $this->assertTrue($definition->is_active);

        $this->put(route('mm05.categories.specifications.update', [
            'category' => $categoryId,
            'definition' => $definition->id,
        ]), [
            'label' => 'Factory Grade Numeric',
            'data_type' => 'number',
        ])->assertRedirect($indexRoute);

        $definition->refresh();
        $this->assertSame('Factory Grade Numeric', $definition->label);
        $this->assertSame('number', $definition->data_type);

        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $this->put(route('mm05.variants.attributes.update', $variant), [
            'attributes' => [[
                'attribute_definition_id' => $definition->id,
                'value_number' => '6.5',
            ]],
        ])->assertRedirect(route('mm05.variants.index'));

        $this->post(route('mm05.categories.specifications.deactivate', [
            'category' => $categoryId,
            'definition' => $definition->id,
        ]))->assertRedirect($indexRoute);

        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_number' => '6.500000',
        ]);

        $this->actingAs($procurement)->get(route('mm05.materials.show', $catalog['material']->id))
            ->assertOk()
            ->assertSee('Factory Grade Numeric')
            ->assertSee('6.5');

        $this->put(route('mm05.categories.specifications.update', [
            'category' => $categoryId,
            'definition' => $otherDefinition->id,
        ]), [
            'label' => 'Must Not Cross Categories',
            'data_type' => 'text',
        ])->assertNotFound();
    }

    public function test_procurement_can_create_update_and_deactivate_attribute_definitions(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();

        $indexRoute = route('mm05.categories.specifications.index', ['category' => $catalog['category']->id]);
        $this->actingAs($procurement)
            ->get($indexRoute)
            ->assertOk()
            ->assertSee($catalog['category']->name)
            ->assertSee('name="_token"', false);

        $this->post(route('mm05.categories.specifications.store', ['category' => $catalog['category']->id]), [
            'label' => 'Lebar Produk',
            'data_type' => 'number',
            'unit_id' => $catalog['unit']->id,
        ])->assertRedirect($indexRoute);

        $definition = MmAttributeDefinition::query()->where('label', 'Lebar Produk')->sole();
        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'category_id' => $catalog['category']->id,
            'data_type' => 'number',
            'unit_id' => $catalog['unit']->id,
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 32767,
        ]);

        $catalog['unit']->update(['is_active' => false]);
        $this->put(route('mm05.categories.specifications.update', ['category' => $catalog['category']->id, 'definition' => $definition->id]), [
            'label' => 'Lebar Produk Revisi',
            'unit_id' => $catalog['unit']->id,
            'is_required' => '0',
        ])->assertRedirect($indexRoute);
        $this->get($indexRoute)
            ->assertOk()
            ->assertSee('(satuan lama; dipertahankan)');
        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'label' => 'Lebar Produk Revisi',
            'is_required' => false,
            'data_type' => 'number',
            'unit_id' => $catalog['unit']->id,
        ]);

        $this->post(route('mm05.categories.specifications.store', ['category' => $catalog['category']->id]), [
            'label' => 'Kode Duplikat',
            'data_type' => 'text',
        ])->assertRedirect($indexRoute);
        $this->assertDatabaseHas('mm_attribute_definitions', [
            'category_id' => $catalog['category']->id,
            'label' => 'Kode Duplikat',
            'is_active' => true,
        ]);

        $this->post(route('mm05.categories.specifications.deactivate', ['category' => $catalog['category']->id, 'definition' => $definition->id]))
            ->assertRedirect($indexRoute);
        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'label' => 'Lebar Produk Revisi',
            'is_active' => false,
        ]);
    }

    public function test_procurement_can_create_and_edit_required_attribute_definition(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $replacementUnit = MmUnit::query()->create([
            'code' => 'UNIT-ALT-'.$this->suffix(),
            'name' => 'Unit Alternatif',
            'dimension' => 'length',
            'is_specification_unit' => true,
        ]);
        $categoryId = (int) $catalog['category']->id;
        $indexRoute = route('mm05.categories.specifications.index', ['category' => $categoryId]);

        $this->actingAs($procurement)->post(route('mm05.categories.specifications.store', ['category' => $categoryId]), [
            'label' => 'Diameter Wajib',
            'data_type' => 'number',
            'unit_id' => $catalog['unit']->id,
            'is_required' => '1',
        ])->assertRedirect($indexRoute);

        $definition = MmAttributeDefinition::query()->where('category_id', $categoryId)->where('label', 'Diameter Wajib')->sole();
        $this->assertTrue($definition->is_required);
        $this->assertSame((int) $catalog['unit']->id, (int) $definition->unit_id);

        $this->put(route('mm05.categories.specifications.update', ['category' => $categoryId, 'definition' => $definition->id]), [
            'label' => 'Diameter Opsional',
            'data_type' => 'number',
            'unit_id' => $replacementUnit->id,
            'is_required' => '0',
        ])->assertRedirect($indexRoute);

        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'label' => 'Diameter Opsional',
            'unit_id' => $replacementUnit->id,
            'is_required' => false,
        ]);

        $this->put(route('mm05.categories.specifications.update', ['category' => $categoryId, 'definition' => $definition->id]), [
            'label' => 'Diameter Wajib',
            'data_type' => 'number',
            'unit_id' => $replacementUnit->id,
            'is_required' => '1',
        ])->assertRedirect($indexRoute);

        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'is_required' => true,
        ]);
    }

    public function test_attribute_definitions_reject_units_not_enabled_for_specifications(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $materialOnlyUnit = MmUnit::query()->create([
            'code' => 'UNIT-NO-SPEC-'.$this->suffix(),
            'name' => 'Material Only Unit '.$this->suffix(),
            'usage_group' => 'both',
            'is_specification_unit' => false,
            'is_available_for_new_material' => true,
        ]);
        $indexRoute = route('mm05.categories.specifications.index', ['category' => $catalog['category']->id]);

        $this->actingAs($procurement)->get($indexRoute)
            ->assertOk()
            ->assertDontSee($materialOnlyUnit->code);

        $this->post(route('mm05.categories.specifications.store', ['category' => $catalog['category']->id]), [
            'label' => 'Panjang dengan satuan material saja',
            'data_type' => 'number',
            'unit_id' => $materialOnlyUnit->id,
        ])->assertSessionHasErrors('unit_id');

        $this->assertDatabaseMissing('mm_attribute_definitions', [
            'category_id' => $catalog['category']->id,
            'label' => 'Panjang dengan satuan material saja',
        ]);
    }

    public function test_procurement_cannot_change_unit_for_a_definition_with_historical_values(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $replacementUnit = MmUnit::query()->create([
            'code' => 'UNIT-HIST-'.$this->suffix(),
            'name' => 'Unit Histori',
            'dimension' => 'length',
            'is_specification_unit' => true,
        ]);
        $definition = $this->createDefinition($catalog['category'], 'number', 'Diameter', false, $catalog['unit']);
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [[
                    'attribute_definition_id' => $definition->id,
                    'value_number' => '12.5',
                ]],
            ])->assertRedirect(route('mm05.variants.index'));

        $this->put(route('mm05.categories.specifications.update', ['category' => $catalog['category']->id, 'definition' => $definition->id]), [
            'label' => $definition->label,
            'data_type' => 'number',
            'unit_id' => $replacementUnit->id,
            'is_required' => '0',
        ])->assertSessionHasErrors(['unit_id']);

        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'unit_id' => $catalog['unit']->id,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_number' => '12.500000',
        ]);
    }

    public function test_procurement_cannot_change_material_category_after_specification_is_saved(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $targetCategory = $this->createCategory();
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $definition = $this->createDefinition($catalog['category'], 'text', 'Color');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Blue'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $this->put(route('mm05.materials.update', $catalog['material']), [
            'category_id' => $targetCategory->id,
        ])->assertSessionHasErrors([
            'category_id' => 'Kategori material tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
        ]);

        $this->assertDatabaseHas('mm_materials', [
            'id' => $catalog['material']->id,
            'category_id' => $catalog['category']->id,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Blue',
        ]);
    }

    public function test_procurement_cannot_change_attribute_category_after_specification_is_saved(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $targetCategory = $this->createCategory();
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $definition = $this->createDefinition($catalog['category'], 'text', 'Color');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Blue'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $this->put(route('mm05.categories.specifications.update', ['category' => $targetCategory->id, 'definition' => $definition->id]), [
            'label' => 'Tidak boleh pindah kategori',
            'data_type' => 'text',
        ])->assertNotFound();

        $this->assertDatabaseHas('mm_attribute_definitions', [
            'id' => $definition->id,
            'category_id' => $catalog['category']->id,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Blue',
        ]);
    }

    public function test_procurement_can_manage_variants_and_preserve_values_on_variant_update(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $sku = 'SKU-'.$this->suffix();

        $this->actingAs($procurement)
            ->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee('Varian Material')
            ->assertSee('name="_token"', false);

        $this->post(route('mm05.variants.store'), [
            'material_id' => $catalog['material']->id,
            'sku' => $sku,
            'variant_name' => 'Produk Ukuran M',
            'base_unit_id' => $catalog['unit']->id,
            'specification_note' => 'Catatan awal',
            'is_active' => '0',
            'created_by' => 999999,
        ])->assertRedirect(route('mm05.variants.index'));

        $variant = MmMaterialVariant::query()->where('sku', $sku)->sole();
        $this->assertSame($procurement->id, $variant->created_by);
        $this->assertTrue($variant->is_active);

        $definition = $this->createDefinition($catalog['category'], 'text', 'Warna');
        $this->put(route('mm05.variants.attributes.update', $variant), [
            'attributes' => [
                [
                    'attribute_definition_id' => $definition->id,
                    'value_text' => 'Biru',
                ],
            ],
        ])->assertRedirect(route('mm05.variants.index'));
        $value = $variant->attributes()->where('attribute_definition_id', $definition->id)->sole();

        $catalog['unit']->update(['is_active' => false]);
        $catalog['material']->update(['is_active' => false]);
        $this->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee('(nonaktif; dipertahankan)');

        $this->put(route('mm05.variants.update', $variant), [
            'material_id' => $catalog['material']->id,
            'base_unit_id' => $catalog['unit']->id,
            'variant_name' => 'Produk Ukuran M Revisi',
            'specification_note' => 'Catatan diperbarui',
            'updated_by' => 999999,
            'is_active' => '0',
        ])->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'variant_name' => 'Produk Ukuran M Revisi',
            'specification_note' => 'Catatan diperbarui',
            'updated_by' => $procurement->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'id' => $value->id,
            'variant_id' => $variant->id,
            'value_text' => 'Biru',
        ]);

        $this->post(route('mm05.variants.deactivate', $variant))
            ->assertRedirect(route('mm05.variants.index'));
        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'sku' => $sku,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', ['id' => $value->id, 'value_text' => 'Biru']);
    }

    public function test_asst_manager_can_search_active_catalog_by_material_sku_and_filters(): void
    {
        $asstManager = $this->createUserWithRole('ASST_MANAGER_PRODUKSI');
        $catalog = $this->createCatalog();
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $variant->update(['sku' => 'SKU-CATALOG-'.$this->suffix(), 'variant_name' => 'Catalog Variant']);

        $sameCategoryMaterial = $this->createMaterial($catalog['category'], 'Second catalog material');
        $sameCategoryVariant = $this->createVariant($sameCategoryMaterial, $catalog['unit']);
        $otherCategory = $this->createCategory();
        $otherCategoryMaterial = $this->createMaterial($otherCategory, 'Other category material');
        $otherCategoryVariant = $this->createVariant($otherCategoryMaterial, $catalog['unit']);

        $this->actingAs($asstManager)
            ->get(route('mm05.variants.index', ['q' => $catalog['material']->code]))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertDontSee($sameCategoryVariant->sku)
            ->assertDontSee($otherCategoryVariant->sku);

        $this->get(route('mm05.variants.index', ['q' => $catalog['material']->name]))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertDontSee($sameCategoryVariant->sku);

        $this->get(route('mm05.variants.index', ['q' => $variant->sku]))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertDontSee($sameCategoryVariant->sku);

        $this->get(route('mm05.variants.index', ['q' => $variant->variant_name]))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertDontSee($sameCategoryVariant->sku);

        $this->get(route('mm05.variants.index', ['category_id' => $catalog['category']->id]))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertSee($sameCategoryVariant->sku)
            ->assertDontSee($otherCategoryVariant->sku);

        $this->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertDontSee($sameCategoryVariant->sku)
            ->assertDontSee($otherCategoryVariant->sku);
    }

    public function test_asst_manager_can_read_and_search_all_saved_variant_specifications(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $textDefinition = $this->createDefinition($catalog['category'], 'text', 'Warna');
        $attributeUnit = MmUnit::query()->create([
            'code' => $this->code('SPECUNIT'),
            'name' => 'Specification Unit '.$this->suffix(),
            'dimension' => 'length',
            'is_specification_unit' => true,
        ]);
        $numberDefinition = $this->createDefinition($catalog['category'], 'number', 'Panjang', false, $attributeUnit);
        $booleanDefinition = $this->createDefinition($catalog['category'], 'boolean', 'Tahan Air');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $textDefinition->id, 'value_text' => 'Biru Katalog'],
                    ['attribute_definition_id' => $numberDefinition->id, 'value_number' => '12.5'],
                    ['attribute_definition_id' => $booleanDefinition->id, 'value_boolean' => '0'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $asstManager = $this->createUserWithRole('ASST_MANAGER_PRODUKSI');
        $this->actingAs($asstManager)
            ->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertSee($textDefinition->label)
            ->assertSee('Biru Katalog')
            ->assertSee($numberDefinition->label)
            ->assertSee('12.500000')
            ->assertSee($attributeUnit->code)
            ->assertSee($booleanDefinition->label)
            ->assertSee('Tidak')
            ->assertDontSee(route('mm05.variants.update', $variant))
            ->assertDontSee(route('mm05.variants.attributes.update', $variant))
            ->assertDontSee('Simpan Nilai Spesifikasi');

        $this->get(route('mm05.variants.index', ['q' => 'Biru Katalog']))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertSee('Biru Katalog');

        $this->get(route('mm05.variants.index', ['q' => '12.5']))
            ->assertOk()
            ->assertSee($variant->sku);

        $this->get(route('mm05.variants.index', ['q' => 'Tidak']))
            ->assertOk()
            ->assertSee($variant->sku);
    }

    public function test_asst_manager_catalog_hides_inactive_materials_and_variants(): void
    {
        $asstManager = $this->createUserWithRole('ASST_MANAGER_PRODUKSI');
        $catalog = $this->createCatalog();
        $activeVariant = $this->createVariant($catalog['material'], $catalog['unit']);

        $inactiveVariant = $this->createVariant($catalog['material'], $catalog['unit']);
        $inactiveVariant->update(['is_active' => false]);

        $inactiveMaterial = $this->createMaterial($catalog['category'], 'Inactive catalog material');
        $variantOfInactiveMaterial = $this->createVariant($inactiveMaterial, $catalog['unit']);
        $inactiveMaterial->update(['is_active' => false]);

        $this->actingAs($asstManager)
            ->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee($activeVariant->sku)
            ->assertDontSee($inactiveVariant->sku)
            ->assertDontSee($inactiveMaterial->code)
            ->assertDontSee($variantOfInactiveMaterial->sku);
    }

    public function test_procurement_cannot_reassign_variant_with_specifications_and_preserves_all_values(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $otherMaterial = $this->createMaterial($otherCategory, 'Other Material');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $definition = $this->createDefinition($catalog['category'], 'text', 'Color');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Blue'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $attributesBefore = $variant->attributes()
            ->orderBy('id')
            ->get(['id', 'variant_id', 'attribute_definition_id', 'value_text', 'value_number', 'value_boolean'])
            ->toArray();
        $originalSku = $variant->sku;
        $changedSku = $this->code('SKU-CHANGED');

        $this->put(route('mm05.variants.update', $variant), [
            'material_id' => $otherMaterial->id,
            'sku' => $changedSku,
            'variant_name' => 'Changed Variant Name',
        ])->assertSessionHasErrors([
            'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
        ]);

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
            'sku' => $originalSku,
            'variant_name' => $variant->variant_name,
        ]);
        $this->assertDatabaseMissing('mm_material_variants', ['sku' => $changedSku]);
        $this->assertSame(
            $attributesBefore,
            $variant->attributes()
                ->orderBy('id')
                ->get(['id', 'variant_id', 'attribute_definition_id', 'value_text', 'value_number', 'value_boolean'])
                ->toArray(),
        );
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Blue',
        ]);
    }

    public function test_procurement_cannot_reassign_variant_with_specifications_within_the_same_category(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherMaterial = $this->createMaterial($catalog['category'], 'Same Category Material');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $definition = $this->createDefinition($catalog['category'], 'text', 'Color');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Blue'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $this->put(route('mm05.variants.update', $variant), [
            'material_id' => $otherMaterial->id,
        ])->assertSessionHasErrors([
            'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
        ]);

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Blue',
        ]);
    }

    public function test_procurement_cannot_reassign_variant_with_supplier_quote_history(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $otherMaterial = $this->createMaterial($otherCategory, 'Other Material');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $supplier = $this->createSupplier();
        $quote = $this->createSupplierQuote($variant, $supplier, $catalog['unit']);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.update', $variant), [
                'material_id' => $otherMaterial->id,
                'sku' => $this->code('SKU-CHANGED'),
                'variant_name' => 'Changed Name',
            ])->assertSessionHasErrors([
                'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki histori penawaran supplier.',
            ]);

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
            'sku' => $variant->sku,
            'variant_name' => $variant->variant_name,
        ]);
        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_procurement_cannot_reassign_variant_with_material_price_history(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $otherMaterial = $this->createMaterial($otherCategory, 'Other Material');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $price = $this->createMaterialPrice($variant, $catalog['unit']);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.update', $variant), [
                'material_id' => $otherMaterial->id,
            ])->assertSessionHasErrors([
                'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki histori harga.',
            ]);

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'id' => $price->id,
            'material_variant_id' => $variant->id,
            'amount' => '1000.0000',
        ]);
    }

    public function test_procurement_cannot_reassign_variant_with_multiple_history_types(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $otherMaterial = $this->createMaterial($otherCategory, 'Other Material');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $supplier = $this->createSupplier();
        $quote = $this->createSupplierQuote($variant, $supplier, $catalog['unit']);
        $price = $this->createMaterialPrice($variant, $catalog['unit'], $quote);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.update', $variant), [
                'material_id' => $otherMaterial->id,
            ])->assertSessionHasErrors([
                'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki histori penawaran supplier.',
            ]);

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
        ]);
        $this->assertDatabaseHas('mm_supplier_quotes', ['id' => $quote->id, 'material_variant_id' => $variant->id]);
        $this->assertDatabaseHas('mm_material_prices', ['id' => $price->id, 'material_variant_id' => $variant->id]);
    }

    public function test_procurement_can_reassign_variant_without_specifications_to_active_material(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $otherMaterial = $this->createMaterial($otherCategory, 'Other Material');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.update', $variant), [
                'material_id' => $otherMaterial->id,
            ])
            ->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $otherMaterial->id,
            'sku' => $variant->sku,
            'variant_name' => $variant->variant_name,
        ]);
        $this->assertSame(0, $variant->attributes()->count());
    }

    public function test_procurement_cannot_reassign_variant_to_inactive_material(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $inactiveMaterial = $this->createMaterial($otherCategory, 'Inactive Material');
        $inactiveMaterial->update(['is_active' => false]);
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.update', $variant), [
                'material_id' => $inactiveMaterial->id,
            ])
            ->assertSessionHasErrors('material_id');

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
        ]);
    }

    public function test_procurement_can_update_variant_name_without_changing_parent_with_specifications(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $definition = $this->createDefinition($catalog['category'], 'text', 'Color');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Green'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $this->put(route('mm05.variants.update', $variant), [
            'material_id' => $catalog['material']->id,
            'variant_name' => 'Renamed Variant',
        ])->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
            'sku' => $variant->sku,
            'variant_name' => 'Renamed Variant',
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Green',
        ]);
    }

    public function test_procurement_can_update_variant_sku_without_changing_parent_with_specifications(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $definition = $this->createDefinition($catalog['category'], 'text', 'Color');
        $updatedSku = $this->code('SKU-UPDATED');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Green'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        $this->put(route('mm05.variants.update', $variant), [
            'material_id' => $catalog['material']->id,
            'sku' => $updatedSku,
        ])->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'material_id' => $catalog['material']->id,
            'sku' => $updatedSku,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Green',
        ]);
    }

    public function test_variant_attribute_values_enforce_type_and_category_but_remain_optional(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $textDefinition = $this->createDefinition($catalog['category'], 'text', 'Warna', false);
        $numberDefinition = $this->createDefinition($catalog['category'], 'number', 'Panjang', true, $catalog['unit']);
        $booleanDefinition = $this->createDefinition($catalog['category'], 'boolean', 'Tahan Air');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $textDefinition->id, 'value_text' => 'Hitam'],
                    ['attribute_definition_id' => $numberDefinition->id, 'value_number' => '12.5'],
                    ['attribute_definition_id' => $booleanDefinition->id, 'value_boolean' => '1'],
                ],
            ])
            ->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $textDefinition->id,
            'value_text' => 'Hitam',
            'value_number' => null,
            'value_boolean' => null,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $numberDefinition->id,
            'value_text' => null,
            'value_number' => '12.500000',
            'value_boolean' => null,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $booleanDefinition->id,
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => true,
        ]);
        $this->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee('Hitam')
            ->assertSee('value="12.500000"', false);

        $this->put(route('mm05.variants.attributes.update', $variant), [
            'attributes' => [
                ['attribute_definition_id' => $textDefinition->id, 'value_text' => 'Merah'],
                ['attribute_definition_id' => $booleanDefinition->id, 'value_boolean' => '0'],
            ],
        ])->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $textDefinition->id,
            'value_text' => 'Merah',
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $numberDefinition->id,
            'value_number' => '12.500000',
        ]);

        $this->put(route('mm05.variants.attributes.update', $variant), [
            'attributes' => [
                ['attribute_definition_id' => $textDefinition->id, 'value_text' => 'Hijau'],
                ['attribute_definition_id' => $textDefinition->id, 'value_text' => 'Kuning'],
            ],
        ])->assertSessionHasErrors('attributes');
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $textDefinition->id,
            'value_text' => 'Merah',
        ]);
    }

    public function test_duplicate_sku_invalid_material_and_cross_category_attribute_are_rejected(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $otherCategory = $this->createCategory();
        $foreignDefinition = $this->createDefinition($otherCategory, 'text', 'Atribut Asing');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $sku = $this->code('SKU');

        $this->actingAs($procurement)
            ->post(route('mm05.variants.store'), [
                'material_id' => 999999999,
                'sku' => $sku,
                'variant_name' => 'Material Tidak Ada',
                'base_unit_id' => $catalog['unit']->id,
            ])->assertSessionHasErrors('material_id');

        $this->post(route('mm05.variants.store'), [
            'material_id' => $catalog['material']->id,
            'sku' => $variant->sku,
            'variant_name' => 'SKU Duplikat',
            'base_unit_id' => $catalog['unit']->id,
        ])->assertSessionHasErrors('sku');

        $this->put(route('mm05.variants.attributes.update', $variant), [
            'attributes' => [
                ['attribute_definition_id' => $foreignDefinition->id, 'value_text' => 'Tidak sesuai kategori'],
            ],
        ])->assertSessionHasErrors('attributes');

        $localDefinition = $this->createDefinition($catalog['category'], 'text', 'Atribut Lokal');
        $this->put(route('mm05.variants.attributes.update', $variant), [
            'attributes' => [
                ['attribute_definition_id' => $localDefinition->id, 'value_text' => 'Nilai teks', 'value_number' => '3'],
            ],
        ])->assertSessionHasErrors('attributes');

        $this->post(route('mm05.categories.specifications.store', ['category' => $catalog['category']->id]), [
            'label' => 'Tipe Tidak Sah',
            'data_type' => 'currency',
        ])->assertSessionHasErrors('data_type');

        $this->assertDatabaseMissing('mm_material_variants', ['sku' => $sku]);
        $this->assertDatabaseMissing('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $foreignDefinition->id,
        ]);
    }

    public function test_read_only_roles_can_view_but_direct_mutations_are_forbidden(): void
    {
        $catalog = $this->createCatalog();
        $definition = $this->createDefinition($catalog['category'], 'text', 'Warna');
        $variant = $this->createVariant($catalog['material'], $catalog['unit']);
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $this->actingAs($procurement)
            ->put(route('mm05.variants.attributes.update', $variant), [
                'attributes' => [
                    ['attribute_definition_id' => $definition->id, 'value_text' => 'Read-only catalog value'],
                ],
            ])->assertRedirect(route('mm05.variants.index'));

        foreach (['ADMIN_BPM', 'ADMIN_PRODUKSI', 'ASST_MANAGER_PRODUKSI'] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            $indexRoute = route('mm05.categories.specifications.index', ['category' => $catalog['category']->id]);
            $this->actingAs($user)->get($indexRoute)->assertOk();
            $this->get(route('mm05.variants.index'))
                ->assertOk()
                ->assertSee('Read-only catalog value')
                ->assertDontSee(route('mm05.variants.update', $variant))
                ->assertDontSee(route('mm05.variants.attributes.update', $variant));

            $this->post(route('mm05.categories.specifications.store', ['category' => $catalog['category']->id]), [
                'label' => 'Tidak Diizinkan',
                'data_type' => 'text',
            ])->assertForbidden();
            $this->put(route('mm05.categories.specifications.update', ['category' => $catalog['category']->id, 'definition' => $definition->id]), ['label' => 'Tidak Diizinkan'])
                ->assertForbidden();
            $this->post(route('mm05.categories.specifications.deactivate', ['category' => $catalog['category']->id, 'definition' => $definition->id]))->assertForbidden();
            $this->post(route('mm05.variants.store'), [
                'material_id' => $catalog['material']->id,
                'sku' => $this->code('NOPE'),
                'variant_name' => 'Tidak Diizinkan',
                'base_unit_id' => $catalog['unit']->id,
            ])->assertForbidden();
            $this->put(route('mm05.variants.update', $variant), ['variant_name' => 'Tidak Diizinkan'])
                ->assertForbidden();
            $this->post(route('mm05.variants.deactivate', $variant))->assertForbidden();
            $this->put(route('mm05.variants.attributes.update', $variant), ['attributes' => []])
                ->assertForbidden();
        }

        foreach (['MARKETING', 'DESIGNER'] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            $this->actingAs($user)->get(route('mm05.categories.specifications.index', ['category' => $catalog['category']->id]))->assertForbidden();
            $this->get(route('mm05.variants.index'))->assertForbidden();
            $this->post(route('mm05.categories.specifications.store', ['category' => $catalog['category']->id]), [])->assertForbidden();
            $this->post(route('mm05.variants.store'), [])->assertForbidden();
        }

        $this->assertDatabaseHas('mm_attribute_definitions', ['id' => $definition->id, 'is_active' => true]);
        $this->assertDatabaseHas('mm_material_variants', ['id' => $variant->id, 'is_active' => true]);
    }

    public function test_unauthenticated_users_are_redirected_from_specification_and_variant_routes(): void
    {
        $catalog = $this->createCatalog();
        $categoryId = (int) $catalog['category']->id;
        $this->get(route('mm05.categories.specifications.index', ['category' => $categoryId]))->assertRedirect(route('login'));
        $this->get(route('mm05.variants.index'))->assertRedirect(route('login'));
        $this->post(route('mm05.categories.specifications.store', ['category' => $categoryId]), [])->assertRedirect(route('login'));
        $this->post(route('mm05.variants.store'), [])->assertRedirect(route('login'));
    }

    /**
     * @return array{category: MmCategory, unit: MmUnit, material: MmMaterial}
     */
    private function createCatalog(): array
    {
        $category = $this->createCategory();
        $unit = MmUnit::query()->create([
            'code' => $this->code('UNIT'),
            'name' => 'Unit '.$this->suffix(),
            'dimension' => 'length',
            'is_specification_unit' => true,
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => $this->code('MAT'),
            'name' => 'Material '.$this->suffix(),
        ]);

        return compact('category', 'unit', 'material');
    }

    private function createCategory(): MmCategory
    {
        return MmCategory::query()->create([
            'code' => $this->code('CAT'),
            'name' => 'Category '.$this->suffix(),
        ]);
    }

    private function createMaterial(MmCategory $category, string $name): MmMaterial
    {
        return MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => $this->code('MAT'),
            'name' => $name.' '.$this->suffix(),
        ]);
    }

    private function createSupplier(): MmSupplier
    {
        return MmSupplier::query()->create([
            'code' => $this->code('SUP'),
            'name' => 'Supplier '.$this->suffix(),
        ]);
    }

    private function createSupplierQuote(
        MmMaterialVariant $variant,
        MmSupplier $supplier,
        MmUnit $unit,
    ): MmSupplierQuote {
        return MmSupplierQuote::query()->create([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'amount' => '1000.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-09',
        ]);
    }

    private function createMaterialPrice(
        MmMaterialVariant $variant,
        MmUnit $unit,
        ?MmSupplierQuote $quote = null,
    ): MmMaterialPrice {
        return MmMaterialPrice::query()->create([
            'material_variant_id' => $variant->id,
            'price_unit_id' => $unit->id,
            'source_quote_id' => $quote?->id,
            'price_type' => 'reference',
            'amount' => '1000.0000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
        ]);
    }

    private function createDefinition(
        MmCategory $category,
        string $type,
        string $label,
        bool $required = false,
        ?MmUnit $unit = null,
    ): MmAttributeDefinition {
        return MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => $this->code('ATTR'),
            'label' => $label.' '.$this->suffix(),
            'data_type' => $type,
            'unit_id' => $type === 'number' ? $unit?->id : null,
            'is_required' => $required,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    private function createVariant(MmMaterial $material, MmUnit $unit): MmMaterialVariant
    {
        return MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => $this->code('SKU'),
            'variant_name' => 'Variant '.$this->suffix(),
            'base_unit_id' => $unit->id,
            'is_active' => true,
        ]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $user = User::query()->create([
            'name' => $roleCode.' User',
            'email' => strtolower($roleCode).'-'.$this->suffix().'@mm22.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => str_replace('_', ' ', $roleCode)],
        );
        $user->roles()->attach($role);

        return $user;
    }

    private function code(string $prefix): string
    {
        return $prefix.'-'.$this->suffix();
    }

    private function suffix(): string
    {
        return strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 12));
    }
}
