<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmSpecificationTemplate;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use App\Models\User;
use App\Services\MmMasterDataService;
use App\Services\MmVariantService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MmMaterialWizardV3Test extends TestCase
{
    use DatabaseTransactions;

    public function test_procurement_creates_material_without_variant_input_and_receives_one_internal_variant(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'THICKNESS-'.$catalog['suffix'],
            'label' => 'Ketebalan',
            'data_type' => 'number',
            'unit_id' => $catalog['unit']->id,
            'is_required' => true,
            'is_active' => true,
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Stainless', 'TMP-THICKNESS-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $definition, true, 1);
        $data = [
            'category_id' => $catalog['category']->id,
            'specification_template_id' => $template->id,
            'name' => 'Stainless '.$catalog['suffix'],
            'base_unit_id' => $catalog['unit']->id,
            'attributes' => [$definition->id => '3'],
            'supplier_mode' => 'none',
        ];

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $this->assertMatchesRegularExpression('/\\AMAT-\\d+\\z/', $material->code);
        $this->assertDatabaseCount('mm_material_variants', 1);

        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertSame($catalog['unit']->id, $variant->base_unit_id);
        $this->assertSame($material->name, $variant->variant_name);
        $this->assertStringStartsWith('SYS-'.$material->code, $variant->sku);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_number' => '3.000000',
        ]);
    }

    public function test_text_specification_with_template_unit_is_saved_and_shown(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = $this->createAttribute($catalog['category'], 'Kode Batch', 'text');
        $template = $this->createSpecificationTemplate(
            $catalog['category'],
            'Text specification unit',
            'TMP-TEXT-UNIT-'.$catalog['suffix'],
        );
        $this->attachTemplateAttribute($template, $definition, false, 1, $catalog['unit']->id);
        $data = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'supplier_mode' => 'none',
            'attributes' => [$definition->id => 'BATCH-123'],
        ]);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'BATCH-123',
        ]);

        $this->get(route('mm05.materials.show', $material))
            ->assertOk()
            ->assertSee('Satuan: '.$catalog['unit']->name);
    }

    public function test_required_template_attribute_blocks_material_until_filled(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'REQUIRED-'.$catalog['suffix'],
            'label' => 'Tersertifikasi',
            'data_type' => 'boolean',
            'is_required' => false,
            'is_active' => true,
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Required', 'TMP-REQUIRED-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $definition, true, 1);
        $data = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'supplier_mode' => 'none',
        ]);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasErrors(['attributes.'.$definition->id]);

        $this->assertDatabaseMissing('mm_materials', ['name' => $data['name']]);

        $data['attributes'] = [$definition->id => '0'];
        $this->post(route('mm05.materials.wizard.store'), $data)->assertRedirect();

        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_boolean' => false,
        ]);
    }

    public function test_category_attributes_are_not_implicitly_saved_without_a_template(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'OPTIONAL-'.$catalog['suffix'],
            'label' => 'Catatan Opsional',
            'data_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);
        $data = $this->materialData($catalog, ['supplier_mode' => 'none']);

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $data)->assertRedirect();

        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertDatabaseMissing('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
        ]);
    }

    public function test_material_wizard_rejects_attributes_not_assigned_to_the_selected_template(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $otherCategory = MmCategory::query()->create([
            'code' => 'CAT-OTHER-'.$catalog['suffix'],
            'name' => 'Kategori Lain '.$catalog['suffix'],
        ]);
        $otherDefinition = MmAttributeDefinition::query()->create([
            'category_id' => $otherCategory->id,
            'code' => 'OTHER-'.$catalog['suffix'],
            'label' => 'Atribut Kategori Lain',
            'data_type' => 'text',
            'is_active' => true,
        ]);
        $allowedDefinition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'ALLOWED-'.$catalog['suffix'],
            'label' => 'Atribut Template',
            'data_type' => 'text',
            'is_active' => true,
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Only allowed', 'TMP-ALLOWED-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $allowedDefinition, false, 1);
        $data = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'supplier_mode' => 'none',
            'attributes' => [$otherDefinition->id => 'Tidak boleh tersimpan'],
        ]);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasErrors(['attributes.'.$otherDefinition->id]);

        $this->assertDatabaseMissing('mm_materials', ['name' => $data['name']]);
        $this->assertDatabaseMissing('mm_variant_attributes', ['attribute_definition_id' => $otherDefinition->id]);
    }

    public function test_material_can_be_saved_without_supplier_or_price_and_receives_automatic_code(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'supplier_mode' => 'none',
            'supplier_id' => '999999',
            'new_supplier_name' => 'Must Not Be Saved',
            'price_amount' => '99',
            'price_unit_id' => '999999',
            'currency' => 'BAD',
            'effective_from' => 'not-a-date',
        ]))->assertRedirect();

        $material = MmMaterial::query()->where('name', 'MM05 Wizard V3 '.Str::upper($catalog['suffix']))->sole();
        $this->assertMatchesRegularExpression('/\AMAT-\d+\z/', $material->code);
        $this->assertDatabaseMissing('mm_suppliers', ['name' => 'Must Not Be Saved']);
        $this->assertDatabaseCount('mm_material_prices', 0);
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertSame($catalog['unit']->id, $variant->base_unit_id);
        $this->assertSame($material->name, $variant->variant_name);
        $this->assertStringStartsWith('SYS-'.$material->code, $variant->sku);
    }

    public function test_material_can_use_registered_supplier_and_direct_price_without_creating_another_supplier(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-V3-'.$catalog['suffix'],
            'name' => 'Registered Supplier '.$catalog['suffix'],
            'address' => 'Registered address',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'supplier_mode' => 'existing',
            'supplier_id' => $supplier->id,
            'price_amount' => '125.50',
            'price_unit_id' => $catalog['unit']->id,
            'currency' => 'IDR',
            'effective_from' => '2026-11-12',
            'communication_method' => 'WhatsApp',
            'communication_note' => 'Dikonfirmasi PIC',
        ]))->assertRedirect();

        $material = MmMaterial::query()->where('name', 'MM05 Wizard V3 '.Str::upper($catalog['suffix']))->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertDatabaseCount('mm_suppliers', 1);
        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '125.5000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-12',
            'change_reason' => 'Konfirmasi melalui WhatsApp. Dikonfirmasi PIC',
        ]);
    }

    public function test_duplicate_material_prefills_specs_and_saves_as_new_record_without_copying_price(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'GRADE-DUP-'.$catalog['suffix'],
            'label' => 'Grade Duplikasi',
            'data_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Duplicate', 'TMP-DUP-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $definition, false, 1);
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-DUP-'.$catalog['suffix'],
            'name' => 'Supplier Duplikasi '.$catalog['suffix'],
            'is_active' => true,
        ]);
        $sourceData = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'name' => 'Stainless SS304 3 mm '.$catalog['suffix'],
            'description' => 'Deskripsi asal',
            'attributes' => [$definition->id => 'SS304'],
            'supplier_mode' => 'existing',
            'supplier_id' => $supplier->id,
            'price_amount' => '125.50',
            'price_unit_id' => $catalog['unit']->id,
            'currency' => 'IDR',
            'effective_from' => '2026-11-12',
        ]);

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $sourceData)->assertRedirect();
        $source = MmMaterial::query()->where('name', $sourceData['name'])->sole();
        $sourceVariant = MmMaterialVariant::query()->where('material_id', $source->id)->sole();

        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $sourceVariant->id,
            'supplier_id' => $supplier->id,
            'amount' => '125.5000',
        ]);

        $this->actingAs($user)
            ->get(route('mm05.materials.wizard.create', ['duplicate_from' => $source->id]))
            ->assertOk()
            ->assertSee('value="'.$sourceData['name'].'"', false)
            ->assertSee('>Deskripsi asal</textarea>', false)
            ->assertSee('SS304')
            ->assertDontSee('value="125.50"', false)
            ->assertSee('value="none" checked', false);

        $duplicateData = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'name' => 'Stainless SS304 1 mm '.$catalog['suffix'],
            'description' => 'Deskripsi asal',
            'attributes' => [$definition->id => 'SS316'],
            'supplier_mode' => 'none',
        ]);
        $this->post(route('mm05.materials.wizard.store'), $duplicateData)->assertRedirect();

        $duplicate = MmMaterial::query()->where('name', $duplicateData['name'])->sole();
        $duplicateVariant = MmMaterialVariant::query()->where('material_id', $duplicate->id)->sole();
        $this->assertNotSame($source->code, $duplicate->code);
        $this->assertNotSame($sourceVariant->id, $duplicateVariant->id);
        $this->assertNotSame($sourceVariant->sku, $duplicateVariant->sku);
        $this->assertSame($source->category_id, $duplicate->category_id);
        $this->assertSame($sourceVariant->base_unit_id, $duplicateVariant->base_unit_id);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $duplicateVariant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'SS316',
        ]);
        $this->assertDatabaseCount('mm_material_prices', 1);
        $this->assertDatabaseHas('mm_material_prices', ['material_variant_id' => $sourceVariant->id]);
        $this->assertDatabaseMissing('mm_material_prices', ['material_variant_id' => $duplicateVariant->id]);
    }

    public function test_new_supplier_address_and_contact_phones_are_saved_with_direct_price(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $supplierName = 'New Supplier '.$catalog['suffix'];

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'supplier_mode' => 'new',
            'supplier_id' => '999999',
            'new_supplier_name' => $supplierName,
            'new_supplier_contact' => 'Sari',
            'new_supplier_address' => 'Jl. Industri 10',
            'new_supplier_phones' => ['081234567890', '082233344455'],
            'price_amount' => '200',
            'price_unit_id' => $catalog['unit']->id,
            'currency' => 'USD',
            'effective_from' => '2026-11-12',
            'communication_method' => 'Email',
            'communication_note' => 'Email konfirmasi tersimpan',
        ]))->assertRedirect();

        $supplier = MmSupplier::query()->where('name', $supplierName)->sole();
        $material = MmMaterial::query()->where('name', 'MM05 Wizard V3 '.Str::upper($catalog['suffix']))->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertSame('SUP-'.str_pad((string) ((int) substr($supplier->code, 4)), 3, '0', STR_PAD_LEFT), $supplier->code);
        $this->assertSame('Jl. Industri 10', $supplier->address);
        $this->assertSame('081234567890', $supplier->phone);
        $this->assertSame(['082233344455'], $supplier->contacts()->pluck('phone')->all());
        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '200.0000',
            'currency' => 'USD',
            'effective_from' => '2026-11-12',
            'change_reason' => 'Konfirmasi melalui Email. Email konfirmasi tersimpan',
        ]);
    }

    public function test_wizard_loads_ordered_template_attributes_and_supplier_mode_script_parses(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'WIDTH-'.$catalog['suffix'],
            'label' => 'Lebar V3',
            'data_type' => 'number',
            'unit_id' => $catalog['unit']->id,
            'is_required' => true,
            'is_active' => true,
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Width', 'TMP-WIDTH-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $definition, true, 1);

        $response = $this->actingAs($user)->get(route('mm05.materials.wizard.create'));
        $response->assertOk()
            ->assertSee('Atribut spesifikasi mengikuti template yang dipilih.')
            ->assertDontSee('Atribut hanya mengikuti kategori yang dipilih.')
            ->assertDontSee('class="category-defs', false)
            ->assertSee('templateSelect.addEventListener(\'change\'', false);
        $html = $response->getContent();
        preg_match('/<script>(.*?)<\/script>/s', $html, $script);
        $this->assertNotEmpty($script[1] ?? null);
        $this->assertSame(1, substr_count($script[1], 'const supplierSelect='));
        $this->assertNotSame(0, preg_match('/supplier_mode/', $script[1]));
        $this->assertTrue($definition->exists);

        $this->get(route('mm05.templates.attributes.index', [
            'template' => $template->id,
            'category_id' => $catalog['category']->id,
        ]))
            ->assertOk()
            ->assertJsonPath('attributes.0.label', 'Lebar V3')
            ->assertJsonPath('attributes.0.input_type', 'number')
            ->assertJsonPath('attributes.0.is_required', true)
            ->assertJsonPath('attributes.0.unit', $catalog['unit']->name);
    }

    public function test_material_wizard_filters_active_base_and_price_units_by_usage_group(): void
    {
        $user = $this->createProcurement();
        $suffix = Str::upper(Str::random(8));
        $baseUnit = MmUnit::query()->create([
            'code' => 'UOM-BASE-'.$suffix,
            'name' => 'Base-only unit',
            'usage_group' => 'base',
        ]);
        $priceUnit = MmUnit::query()->create([
            'code' => 'UOM-PRICE-'.Str::upper(Str::random(8)),
            'name' => 'Price-only unit',
            'usage_group' => 'price',
        ]);
        $bothUnit = MmUnit::query()->create([
            'code' => 'UOM-BOTH-'.Str::upper(Str::random(8)),
            'name' => 'Both-usage unit',
            'usage_group' => 'both',
        ]);
        $inactiveUnit = MmUnit::query()->create([
            'code' => 'UOM-OFF-'.Str::upper(Str::random(8)),
            'name' => 'Inactive unit',
            'usage_group' => 'both',
            'is_active' => false,
        ]);
        $specificationOnlyUnit = MmUnit::query()->create([
            'code' => 'UOM-SPEC-'.Str::upper(Str::random(8)),
            'name' => 'Millimeter',
            'usage_group' => null,
            'is_specification_unit' => true,
            'is_available_for_new_material' => false,
        ]);

        $html = $this->actingAs($user)
            ->get(route('mm05.materials.wizard.create'))
            ->assertOk()
            ->getContent();

        preg_match('/<select name="base_unit_id"[^>]*>(.*?)<\\/select>/s', $html, $baseSelect);
        preg_match('/<select name="price_unit_id"[^>]*>(.*?)<\\/select>/s', $html, $priceSelect);

        $this->assertNotEmpty($baseSelect[1] ?? null);
        $this->assertNotEmpty($priceSelect[1] ?? null);
        $this->assertStringContainsString('value="'.$baseUnit->id.'"', $baseSelect[1]);
        $this->assertStringContainsString('value="'.$bothUnit->id.'"', $baseSelect[1]);
        $this->assertStringNotContainsString('value="'.$priceUnit->id.'"', $baseSelect[1]);
        $this->assertStringContainsString('value="'.$priceUnit->id.'"', $priceSelect[1]);
        $this->assertStringContainsString('value="'.$bothUnit->id.'"', $priceSelect[1]);
        $this->assertStringNotContainsString('value="'.$baseUnit->id.'"', $priceSelect[1]);
        $this->assertStringNotContainsString('value="'.$inactiveUnit->id.'"', $html);
        $this->assertStringNotContainsString('value="'.$specificationOnlyUnit->id.'"', $baseSelect[1]);
        $this->assertStringNotContainsString('value="'.$specificationOnlyUnit->id.'"', $priceSelect[1]);
    }

    public function test_material_wizard_rejects_units_outside_their_usage_group(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $baseOnlyUnit = MmUnit::query()->create([
            'code' => 'UOM-BASE-'.$catalog['suffix'],
            'name' => 'Base-only unit',
            'usage_group' => 'base',
        ]);
        $priceOnlyUnit = MmUnit::query()->create([
            'code' => 'UOM-PRICE-'.$catalog['suffix'],
            'name' => 'Price-only unit',
            'usage_group' => 'price',
        ]);
        $specificationOnlyUnit = MmUnit::query()->create([
            'code' => 'UOM-SPEC-'.$catalog['suffix'],
            'name' => 'Millimeter',
            'usage_group' => null,
            'is_specification_unit' => true,
            'is_available_for_new_material' => false,
        ]);
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-UOM-'.$catalog['suffix'],
            'name' => 'UOM Test Supplier',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
                'base_unit_id' => $priceOnlyUnit->id,
                'supplier_mode' => 'none',
            ]))
            ->assertSessionHasErrors('base_unit_id');

        $this->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'base_unit_id' => $baseOnlyUnit->id,
            'supplier_mode' => 'existing',
            'supplier_id' => $supplier->id,
            'price_amount' => '100',
            'price_unit_id' => $baseOnlyUnit->id,
            'currency' => 'IDR',
            'effective_from' => '2026-11-14',
        ]))->assertSessionHasErrors('price_unit_id');

        $this->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'base_unit_id' => $specificationOnlyUnit->id,
            'supplier_mode' => 'none',
        ]))->assertSessionHasErrors('base_unit_id');

        $this->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'supplier_mode' => 'existing',
            'supplier_id' => $supplier->id,
            'price_amount' => '100',
            'price_unit_id' => $specificationOnlyUnit->id,
            'currency' => 'IDR',
            'effective_from' => '2026-11-14',
        ]))->assertSessionHasErrors('price_unit_id');

        $this->assertDatabaseMissing('mm_materials', [
            'name' => 'MM05 Wizard V3 '.$catalog['suffix'],
        ]);
    }

    public function test_material_wizard_saves_conversion_without_creating_a_price(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $priceUnit = MmUnit::query()->create([
            'code' => 'UOM-BOX-'.$catalog['suffix'],
            'name' => 'Box',
            'usage_group' => 'price',
        ]);
        $data = $this->materialData($catalog, [
            'supplier_mode' => 'none',
            'price_unit_id' => $priceUnit->id,
            'conversion_factor' => '100',
        ]);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $variant = $material->variants()->sole();

        $this->assertDatabaseHas('mm_material_unit_conversions', [
            'material_id' => $material->id,
            'base_unit_id' => $catalog['unit']->id,
            'price_unit_id' => $priceUnit->id,
            'conversion_factor' => '100.00000000',
        ]);
        $this->assertDatabaseMissing('mm_material_prices', [
            'material_variant_id' => $variant->id,
        ]);
    }

    public function test_switching_templates_returns_only_the_selected_templates_ordered_attributes(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $catLabels = ['Merek', 'Jenis Cat', 'Warna', 'Kode Warna', 'Finishing', 'Penggunaan', 'Volume', 'Basis Cat'];
        $multiplexLabels = ['Grade', 'Ketebalan', 'Panjang', 'Lebar', 'Jenis Kayu', 'Finishing'];
        $definitions = [];
        foreach (array_unique([...$catLabels, ...$multiplexLabels]) as $label) {
            $definitions[$label] = $this->createAttribute($catalog['category'], $label, 'text');
        }

        $catTemplate = $this->createSpecificationTemplate($catalog['category'], 'Cat', 'TMP-CAT-'.$catalog['suffix']);
        foreach ($catLabels as $index => $label) {
            $this->attachTemplateAttribute($catTemplate, $definitions[$label], false, $index + 1);
        }
        $multiplexTemplate = $this->createSpecificationTemplate($catalog['category'], 'Multiplex', 'TMP-MULTIPLEX-'.$catalog['suffix']);
        foreach ($multiplexLabels as $index => $label) {
            $this->attachTemplateAttribute($multiplexTemplate, $definitions[$label], false, $index + 1);
        }

        $catResponse = $this->actingAs($user)->getJson(route('mm05.templates.attributes.index', [
            'template' => $catTemplate->id,
            'category_id' => $catalog['category']->id,
        ]));
        $catResponse->assertOk()
            ->assertJsonPath('attributes.0.label', 'Merek')
            ->assertJsonPath('attributes.7.label', 'Basis Cat')
            ->assertHeader('Cache-Control', 'no-store, private');

        $multiplexResponse = $this->getJson(route('mm05.templates.attributes.index', [
            'template' => $multiplexTemplate->id,
            'category_id' => $catalog['category']->id,
        ]));
        $multiplexResponse->assertOk()
            ->assertJsonPath('attributes.0.label', 'Grade')
            ->assertJsonPath('attributes.5.label', 'Finishing')
            ->assertJsonMissing(['label' => 'Merek'])
            ->assertJsonMissing(['label' => 'Basis Cat']);

        $this->assertSame($catLabels, array_column($catResponse->json('attributes'), 'label'));
        $this->assertSame($multiplexLabels, array_column($multiplexResponse->json('attributes'), 'label'));
    }

    public function test_template_backend_validates_required_and_typed_values_and_edit_preserves_template_fields(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $volume = $this->createAttribute($catalog['category'], 'Volume', 'number', $catalog['unit']);
        $waterBased = $this->createAttribute($catalog['category'], 'Water based', 'boolean');
        $template = $this->createSpecificationTemplate($catalog['category'], 'Paint', 'TMP-PAINT-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $volume, true, 1, $catalog['unit']->id);
        $this->attachTemplateAttribute($template, $waterBased, false, 2);
        $data = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'supplier_mode' => 'none',
        ]);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasErrors(['attributes.'.$volume->id]);
        $this->assertDatabaseMissing('mm_materials', ['name' => $data['name']]);

        $data['attributes'] = [$volume->id => 'not-a-number'];
        $this->post(route('mm05.materials.wizard.store'), $data)
            ->assertSessionHasErrors(['attributes.'.$volume->id]);
        $this->assertDatabaseMissing('mm_materials', ['name' => $data['name']]);

        $data['attributes'] = [$volume->id => '12.5', $waterBased->id => '0'];
        $this->post(route('mm05.materials.wizard.store'), $data)->assertRedirect();
        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();

        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $volume->id,
            'value_number' => '12.500000',
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $waterBased->id,
            'value_boolean' => false,
        ]);

        $this->get(route('mm05.materials.show', $material->id))
            ->assertOk()
            ->assertSee('Volume')
            ->assertSee('Water based')
            ->assertSee('12.5')
            ->assertSee('Tidak')
            ->assertSee('Satuan: '.$catalog['unit']->name)
            ->assertSee('name="attributes[0][value_number]"', false);

        $this->put(route('mm05.materials.variants.attributes.update', [$material->id, $variant->id]), [
            'attributes' => [
                ['attribute_definition_id' => $volume->id, 'value_number' => '15.5'],
                ['attribute_definition_id' => $waterBased->id, 'value_boolean' => ''],
            ],
        ])->assertRedirect(route('mm05.materials.show', $material->id));

        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $volume->id,
            'value_number' => '15.500000',
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $waterBased->id,
            'value_boolean' => null,
        ]);
    }

    public function test_template_must_be_active_and_match_the_material_category(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $otherCategory = MmCategory::query()->create([
            'code' => 'CAT-OTHER-'.$catalog['suffix'],
            'name' => 'Kategori Lain '.$catalog['suffix'],
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Active', 'TMP-ACTIVE-'.$catalog['suffix']);
        $otherTemplate = $this->createSpecificationTemplate($otherCategory, 'Other category', 'TMP-OTHER-'.$catalog['suffix']);

        $this->actingAs($user)
            ->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
                'specification_template_id' => $otherTemplate->id,
                'supplier_mode' => 'none',
            ]))
            ->assertSessionHasErrors(['specification_template_id']);

        $template->update(['is_active' => false]);
        $this->post(route('mm05.materials.wizard.store'), $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'supplier_mode' => 'none',
        ]))->assertSessionHasErrors(['specification_template_id']);

        $this->assertDatabaseMissing('mm_materials', ['category_id' => $catalog['category']->id]);
    }

    public function test_template_endpoint_keeps_an_inactive_configured_unit_readable_for_legacy_assignments(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $volume = $this->createAttribute($catalog['category'], 'Volume', 'number', $catalog['unit']);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Volume', 'TMP-UNIT-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $volume, false, 1, $catalog['unit']->id);
        $catalog['unit']->update(['is_active' => false]);

        $this->actingAs($user)
            ->getJson(route('mm05.templates.attributes.index', [
                'template' => $template->id,
                'category_id' => $catalog['category']->id,
            ]))
            ->assertOk()
            ->assertJsonPath('attributes.0.unit_id', $catalog['unit']->id)
            ->assertJsonPath('attributes.0.unit', $catalog['unit']->name);
    }

    public function test_material_wizard_does_not_expose_manual_variant_fields(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();

        $this->actingAs($user)
            ->get(route('mm05.materials.wizard.create'))
            ->assertOk()
            ->assertSee('Satuan Dasar')
            ->assertSee('Spesifikasi Material')
            ->assertSee('Simpan Material')
            ->assertDontSee('Varian Utama')
            ->assertDontSee('Nama Varian')
            ->assertDontSee('name="variants[0][sku]"', false)
            ->assertSee($catalog['category']->name);
    }

    public function test_procurement_navigation_hides_variant_menu_but_legacy_route_remains(): void
    {
        $user = $this->createProcurement();

        foreach ([
            route('mm05.workspace'),
            route('mm05.materials.index'),
            route('mm05.suppliers.index'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertDontSee('/material-master/variants');
        }

        $this->actingAs($user)->get(route('mm05.quotes.index'))
            ->assertRedirect(route('mm05.prices.index'));

        $this->actingAs($user)->get(route('mm05.variants.index'))->assertOk();
    }

    public function test_material_detail_uses_material_sections_and_collapses_edit_forms(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'DETAIL-SPEC-'.$catalog['suffix'],
            'label' => 'Detail specification',
            'data_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);
        $data = $this->materialData($catalog, ['supplier_mode' => 'none']);
        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $data)->assertRedirect();
        $material = MmMaterial::query()->where('name', $data['name'])->sole();

        $response = $this->actingAs($user)->get(route('mm05.materials.show', $material->id))->assertOk();
        $response->assertSee('Informasi Material')
            ->assertSee('Spesifikasi')
            ->assertSee('Supplier')
            ->assertSee('Harga Terbaru')
            ->assertSee('Riwayat Harga')
            ->assertDontSee('Varian Utama')
            ->assertDontSee('SYS-'.$material->code);
        $this->assertMatchesRegularExpression(
            '/<dialog\\b[^>]*class="[^"]*mmd-modal[^"]*"[^>]*>.*?Ubah Spesifikasi.*?<\\/dialog>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<button\\b[^>]*data-mmd-open="mmd-modal-edit-spec-[0-9]+"[^>]*>/s',
            $response->getContent()
        );
    }

    public function test_legacy_multi_identity_material_keeps_each_specification_visible_and_blocks_ambiguous_duplication(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'LEGACY-GRADE-'.$catalog['suffix'],
            'label' => 'Grade Historis',
            'data_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);
        $template = $this->createSpecificationTemplate($catalog['category'], 'Legacy records', 'TMP-LEGACY-'.$catalog['suffix']);
        $this->attachTemplateAttribute($template, $definition, false, 1);
        $data = $this->materialData($catalog, [
            'specification_template_id' => $template->id,
            'name' => 'Legacy Multi '.$catalog['suffix'],
            'attributes' => [$definition->id => 'SS304'],
            'supplier_mode' => 'none',
        ]);
        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), $data)->assertRedirect();
        $material = MmMaterial::query()->where('name', $data['name'])->sole();
        $service = app(MmVariantService::class);
        $secondVariant = $service->createVariant([
            'material_id' => $material->id,
            'sku' => 'LEGACY-SECOND-'.$catalog['suffix'],
            'variant_name' => 'Legacy record second',
            'base_unit_id' => $catalog['unit']->id,
        ], $user->id);
        $service->setAttributes($secondVariant, [[
            'attribute_definition_id' => $definition->id,
            'value_text' => 'SS316',
            'value_number' => null,
            'value_boolean' => null,
        ]]);

        $this->actingAs($user)->get(route('mm05.materials.show', $material->id))
            ->assertOk()
            ->assertSee('Catatan identitas historis')
            ->assertSee('SYS-'.$material->code)
            ->assertSee('LEGACY-SECOND-'.$catalog['suffix'])
            ->assertSee('SS304')
            ->assertSee('SS316');

        $this->actingAs($user)
            ->get(route('mm05.materials.wizard.create', ['duplicate_from' => $material->id]))
            ->assertRedirect(route('mm05.materials.show', $material->id))
            ->assertSessionHas('warning');
        $this->assertSame(2, MmMaterialVariant::query()->where('material_id', $material->id)->count());
    }

    public function test_duplicate_supplier_name_is_rejected_instead_of_creating_another_master_record(): void
    {
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-DUP-'.Str::upper(Str::random(8)),
            'name' => 'Duplicate Guard Supplier',
            'is_active' => true,
        ]);

        try {
            app(MmMasterDataService::class)->createSupplier(['name' => ' duplicate guard supplier ']);
            $this->fail('Expected an existing supplier name to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }

        $this->assertDatabaseCount('mm_suppliers', 1);
        $this->assertDatabaseHas('mm_suppliers', ['id' => $supplier->id, 'name' => 'Duplicate Guard Supplier']);
    }

    private function createCatalog(): array
    {
        $suffix = Str::upper(Str::random(8));
        $category = MmCategory::query()->create(['code' => 'CAT-V3-'.$suffix, 'name' => 'Wizard V3 '.$suffix]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-V3-'.$suffix,
            'name' => 'Unit V3 '.$suffix,
            'dimension' => 'count',
            'usage_group' => 'both',
            'is_specification_unit' => true,
            'is_available_for_new_material' => true,
        ]);

        return ['suffix' => $suffix, 'category' => $category, 'unit' => $unit, 'sku' => 'SKU-V3-'.$suffix];
    }

    private function materialData(array $catalog, array $overrides): array
    {
        return array_merge([
            'category_id' => $catalog['category']->id,
            'name' => 'MM05 Wizard V3 '.$catalog['suffix'],
            'base_unit_id' => $catalog['unit']->id,
        ], $overrides);
    }

    private function createAttribute(
        MmCategory $category,
        string $label,
        string $dataType,
        ?MmUnit $unit = null,
    ): MmAttributeDefinition {
        return MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => 'ATTR-'.Str::upper(Str::random(8)),
            'label' => $label,
            'data_type' => $dataType,
            'unit_id' => $unit?->id,
            'is_required' => false,
            'is_active' => true,
        ]);
    }

    private function createSpecificationTemplate(MmCategory $category, string $name, string $code): MmSpecificationTemplate
    {
        return MmSpecificationTemplate::query()->create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function attachTemplateAttribute(
        MmSpecificationTemplate $template,
        MmAttributeDefinition $definition,
        bool $required,
        int $displayOrder,
        ?int $unitId = null,
    ): void {
        $template->templateAttributes()->create([
            'attribute_definition_id' => $definition->id,
            'is_required' => $required,
            'display_order' => $displayOrder,
            'input_type' => $definition->data_type,
            'unit_id' => $unitId ?? $definition->unit_id,
            'unit_id_is_explicit' => ($unitId ?? $definition->unit_id) !== null,
        ]);
    }

    private function createProcurement(): User
    {
        $suffix = Str::lower(Str::random(10));
        $user = User::query()->create([
            'name' => 'MM05 Procurement '.$suffix,
            'email' => $suffix.'@mm05.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(['code' => 'PROCUREMENT'], ['name' => 'Procurement']);
        $user->roles()->attach($role);

        return $user;
    }
}
