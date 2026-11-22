<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialPrice;
use App\Models\MmMaterialUnitConversion;
use App\Models\MmMaterialVariant;
use App\Models\MmSpecificationTemplate;
use App\Models\MmSpecificationTemplateAttribute;
use App\Models\MmSupplier;
use App\Models\MmSupplierQuote;
use App\Models\MmUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MmAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_bpm_can_read_all_mm_pages_without_mutation_controls(): void
    {
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $category = MmCategory::query()->create(['code' => 'CAT-READ', 'name' => 'Read Category']);
        $unit = MmUnit::query()->create(['code' => 'UNIT-READ', 'name' => 'Read Unit', 'dimension' => 'count']);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-READ',
            'name' => 'Read Material',
        ]);

        $this->actingAs($admin)->get(route('mm05.workspace'))->assertOk();

        $materialResponse = $this->get(route('mm05.materials.index'));
        $materialResponse->assertOk()
            ->assertSee('Read Material')
            ->assertDontSee('method="POST" action="'.route('mm05.materials.store').'"', false)
            ->assertDontSee('action="'.route('mm05.materials.update', $material->id).'"', false)
            ->assertDontSee(route('mm05.materials.deactivate', $material->id));

        $categoryResponse = $this->get(route('mm05.categories.index'));
        $categoryResponse->assertOk()
            ->assertSee('Read Category')
            ->assertSee(route('mm05.categories.specifications.index', ['category' => $category->id]))
            ->assertDontSee('method="POST" action="'.route('mm05.categories.store').'"', false)
            ->assertDontSee('action="'.route('mm05.categories.update', $category->id).'"', false)
            ->assertDontSee(route('mm05.categories.deactivate', $category->id));

        $unitResponse = $this->get(route('mm05.units.index'));
        $unitResponse->assertOk()
            ->assertSee('Read Unit')
            ->assertDontSee('method="POST" action="'.route('mm05.units.store').'"', false)
            ->assertDontSee(route('mm05.units.update', $unit->id))
            ->assertDontSee(route('mm05.units.deactivate', $unit->id))
            ->assertDontSee(route('mm05.units.delete', $unit->id));

        $supplierResponse = $this->get(route('mm05.suppliers.index'));
        $supplierResponse->assertOk()
            ->assertDontSee('method="POST" action="'.route('mm05.suppliers.store').'"', false);
    }

    public function test_admin_bpm_cannot_mutate_material_category_or_unit_by_direct_requests(): void
    {
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $category = MmCategory::query()->create(['code' => 'CAT-LOCK', 'name' => 'Locked Category']);
        $unit = MmUnit::query()->create(['code' => 'UNIT-LOCK', 'name' => 'Locked Unit', 'dimension' => 'count']);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-LOCK',
            'name' => 'Locked Material',
        ]);

        $this->actingAs($admin)
            ->post(route('mm05.materials.store'), [
                'category_id' => $category->id,
                'code' => 'MAT-UNAUTHORIZED',
                'name' => 'Unauthorized Material',
            ])->assertForbidden();

        $this->post(route('mm05.materials.update', $material->id), [
            '_method' => 'PUT',
            'category_id' => $category->id,
            'code' => 'MAT-CHANGED',
            'name' => 'Changed Material',
        ])->assertForbidden();
        $this->post(route('mm05.materials.deactivate', $material->id))->assertForbidden();

        $this->post(route('mm05.categories.store'), [
            'code' => 'CAT-UNAUTHORIZED',
            'name' => 'Unauthorized Category',
        ])->assertForbidden();
        $this->put(route('mm05.categories.update', $category->id), [
            'code' => 'CAT-CHANGED',
            'name' => 'Changed Category',
        ])->assertForbidden();
        $this->post(route('mm05.categories.deactivate', $category->id))->assertForbidden();

        $this->post(route('mm05.units.store'), [
            'code' => 'UNIT-UNAUTHORIZED',
            'name' => 'Unauthorized Unit',
            'dimension' => 'length',
        ])->assertForbidden();
        $this->put(route('mm05.units.update', $unit->id), [
            'code' => 'UNIT-CHANGED',
            'name' => 'Changed Unit',
            'dimension' => 'length',
        ])->assertForbidden();
        $this->post(route('mm05.units.deactivate', $unit->id))->assertForbidden();
        $this->delete(route('mm05.units.delete', $unit->id))->assertForbidden();

        $this->assertDatabaseMissing('mm_materials', ['code' => 'MAT-UNAUTHORIZED']);
        $this->assertDatabaseHas('mm_materials', [
            'id' => $material->id,
            'code' => 'MAT-LOCK',
            'name' => 'Locked Material',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('mm_categories', ['code' => 'CAT-UNAUTHORIZED']);
        $this->assertDatabaseHas('mm_categories', [
            'id' => $category->id,
            'code' => 'CAT-LOCK',
            'name' => 'Locked Category',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('mm_units', ['code' => 'UNIT-UNAUTHORIZED']);
        $this->assertDatabaseHas('mm_units', [
            'id' => $unit->id,
            'code' => 'UNIT-LOCK',
            'name' => 'Locked Unit',
            'is_active' => true,
        ]);
    }

    public function test_procurement_can_open_all_implemented_mm_pages_and_manage_forms(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $this->actingAs($procurement)->get(route('mm05.workspace'))->assertOk();
        $this->get(route('mm05.materials.index'))
            ->assertOk()
            ->assertSee('id="openMaterialModal"', false)
            ->assertSee('mmMaterialModal', false)
            ->assertSee('const formUrl =', false);
        $this->get(route('mm05.categories.index'))
            ->assertOk()
            ->assertSee('method="POST" action="'.route('mm05.categories.store').'"', false);
        $this->get(route('mm05.units.index'))
            ->assertOk()
            ->assertSee('method="POST" action="'.route('mm05.units.store').'"', false);
        $this->get(route('mm05.suppliers.index'))
            ->assertOk()
            ->assertSee('method="POST" action="'.route('mm05.suppliers.store').'"', false)
            ->assertDontSee('name="code"', false);
    }

    public function test_procurement_can_create_supplier_with_an_automatic_unique_code(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $this->actingAs($procurement)->post(route('mm05.suppliers.store'), [
            'code' => 'SUP-MANUAL',
            'name' => 'Supplier Original',
            'contact_name' => 'Contact Original',
            'phone' => '08123456789',
            'email' => 'supplier@example.test',
            'notes' => 'Initial supplier note',
        ])->assertRedirect(route('mm05.suppliers.index'));

        $supplier = MmSupplier::query()->where('name', 'Supplier Original')->sole();
        $this->assertMatchesRegularExpression('/\ASUP-\d{3,}\z/', $supplier->code);
        $this->assertDatabaseMissing('mm_suppliers', ['code' => 'SUP-MANUAL']);
        $this->assertDatabaseHas('mm_suppliers', ['id' => $supplier->id, 'is_active' => true]);

        $this->post(route('mm05.suppliers.deactivate', $supplier))
            ->assertRedirect(route('mm05.suppliers.index'));
        $this->post(route('mm05.suppliers.store'), ['name' => 'Supplier Second'])
            ->assertRedirect(route('mm05.suppliers.index'));

        $secondSupplier = MmSupplier::query()->where('name', 'Supplier Second')->sole();
        $this->assertNotSame($supplier->code, $secondSupplier->code);
        $this->assertMatchesRegularExpression('/\ASUP-\d{3,}\z/', $secondSupplier->code);
        $nextSequence = (int) substr($supplier->code, 4) + 1;
        $this->assertSame(
            'SUP-'.str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT),
            $secondSupplier->code,
        );
        $this->assertDatabaseHas('mm_suppliers', [
            'id' => $supplier->id,
            'code' => $supplier->code,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('mm_suppliers', ['id' => $secondSupplier->id, 'is_active' => true]);
    }

    public function test_procurement_can_edit_search_filter_deactivate_and_reactivate_supplier_without_deleting_history(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $suffix = Str::upper(Str::random(8));
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-OLD-'.$suffix,
            'name' => 'Supplier Original '.$suffix,
            'contact_name' => 'Contact Original',
            'phone' => '08123456789',
            'email' => 'supplier@example.test',
            'notes' => 'Initial supplier note',
        ]);
        $category = MmCategory::query()->create([
            'code' => 'CAT-SUP-'.$suffix,
            'name' => 'Supplier Test Category '.$suffix,
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-SUP-'.$suffix,
            'name' => 'Supplier Test Unit '.$suffix,
            'dimension' => 'count',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-SUP-'.$suffix,
            'name' => 'Supplier Test Material '.$suffix,
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-SUP-'.$suffix,
            'variant_name' => 'Supplier Test Variant '.$suffix,
            'base_unit_id' => $unit->id,
        ]);
        $quote = MmSupplierQuote::query()->create([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'amount' => '1000.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-09',
        ]);
        $price = MmMaterialPrice::query()->create([
            'material_variant_id' => $variant->id,
            'price_unit_id' => $unit->id,
            'source_quote_id' => $quote->id,
            'price_type' => 'reference',
            'amount' => '1000.0000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
        ]);

        $this->actingAs($procurement)->get(route('mm05.suppliers.index', ['q' => $supplier->code]))
            ->assertOk()
            ->assertSee($supplier->code)
            ->assertSee($supplier->name);
        $this->get(route('mm05.suppliers.index', ['q' => $suffix]))
            ->assertOk()
            ->assertSee($supplier->code);

        $this->put(route('mm05.suppliers.update', $supplier), [
            'code' => 'SUP-CHANGED',
            'name' => 'Supplier Updated '.$suffix,
            'contact_name' => 'Contact Updated',
            'phone' => '08111111111',
            'email' => 'updated@example.test',
            'notes' => 'Updated supplier note',
        ])->assertRedirect(route('mm05.suppliers.index'));

        $this->assertDatabaseHas('mm_suppliers', [
            'id' => $supplier->id,
            'code' => 'SUP-OLD-'.$suffix,
            'name' => 'Supplier Updated '.$suffix,
            'contact_name' => 'Contact Updated',
            'phone' => '08111111111',
            'email' => 'updated@example.test',
            'notes' => 'Updated supplier note',
            'is_active' => true,
        ]);

        $this->get(route('mm05.suppliers.index', ['q' => 'Updated '.$suffix]))
            ->assertOk()
            ->assertSee($supplier->code);
        $this->get(route('mm05.suppliers.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($supplier->code);

        $this->post(route('mm05.suppliers.deactivate', $supplier))
            ->assertRedirect(route('mm05.suppliers.index'));
        $this->assertDatabaseHas('mm_suppliers', [
            'id' => $supplier->id,
            'code' => 'SUP-OLD-'.$suffix,
            'name' => 'Supplier Updated '.$suffix,
            'notes' => 'Updated supplier note',
            'is_active' => false,
        ]);
        $this->assertModelExists($quote);
        $this->assertModelExists($price);

        $this->get(route('mm05.suppliers.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($supplier->code)
            ->assertSee('Supplier Updated '.$suffix)
            ->assertSee(route('mm05.suppliers.activate', $supplier));
        $this->get(route('mm05.suppliers.index', ['status' => 'active']))
            ->assertOk()
            ->assertDontSee($supplier->code);

        $this->post(route('mm05.suppliers.activate', $supplier))
            ->assertRedirect(route('mm05.suppliers.index'));
        $this->assertDatabaseHas('mm_suppliers', [
            'id' => $supplier->id,
            'code' => 'SUP-OLD-'.$suffix,
            'is_active' => true,
        ]);
        $this->assertModelExists($quote);
        $this->assertModelExists($price);
        $this->get(route('mm05.suppliers.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($supplier->code);
        $this->get(route('mm05.suppliers.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertDontSee($supplier->code);
    }

    public function test_procurement_can_create_update_and_deactivate_material_categories_and_units(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $this->actingAs($procurement)->post(route('mm05.categories.store'), [
            'code' => 'CAT-PROC',
            'name' => 'Procurement Category',
        ])->assertRedirect(route('mm05.categories.index'));
        $category = MmCategory::query()->where('name', 'Procurement Category')->sole();
        $generatedCode = $category->code;
        $this->assertMatchesRegularExpression('/\\ACAT-\\d{3,}\\z/', $generatedCode);

        $this->put(route('mm05.categories.update', $category->id), [
            'code' => 'CAT-PROC-UPDATED',
            'name' => 'Updated Procurement Category',
        ])->assertRedirect(route('mm05.categories.index'));
        $this->assertDatabaseHas('mm_categories', [
            'id' => $category->id,
            'code' => $generatedCode,
            'name' => 'Updated Procurement Category',
        ]);
        $this->post(route('mm05.categories.deactivate', $category->id))
            ->assertRedirect(route('mm05.categories.index'));
        $this->assertDatabaseHas('mm_categories', ['id' => $category->id, 'is_active' => false]);

        $this->post(route('mm05.units.store'), [
            'code' => 'UOM-CLIENT-CODE',
            'name' => 'Procurement Unit',
            'dimension' => 'length',
            'usage_group' => 'base',
        ])->assertRedirect(route('mm05.units.index'));
        $unit = MmUnit::query()->where('name', 'Procurement Unit')->sole();
        $generatedUnitCode = $unit->code;
        $this->assertMatchesRegularExpression('/\\AUOM-\\d{3,}\\z/', $generatedUnitCode);

        $this->put(route('mm05.units.update', $unit->id), [
            'code' => 'UOM-MANIPULATED',
            'name' => 'Updated Procurement Unit',
            'dimension' => 'area',
            'usage_group' => 'both',
        ])->assertRedirect(route('mm05.units.index'));
        $this->assertDatabaseHas('mm_units', [
            'id' => $unit->id,
            'code' => $generatedUnitCode,
            'name' => 'Updated Procurement Unit',
            'dimension' => 'area',
            'usage_group' => 'both',
        ]);

        $this->post(route('mm05.units.store'), [
            'name' => 'Price Procurement Unit',
            'dimension' => 'volume',
            'usage_group' => 'price',
        ])->assertRedirect(route('mm05.units.index'));
        $this->assertDatabaseHas('mm_units', [
            'name' => 'Price Procurement Unit',
            'usage_group' => 'price',
        ]);
        $this->post(route('mm05.units.deactivate', $unit->id))
            ->assertRedirect(route('mm05.units.index'));
        $this->assertDatabaseHas('mm_units', ['id' => $unit->id, 'is_active' => false]);

        $activeCategory = MmCategory::query()->create(['code' => 'CAT-MAT', 'name' => 'Material Category']);
        $this->post(route('mm05.materials.store'), [
            'category_id' => $activeCategory->id,
            'code' => 'MAT-PROC',
            'name' => 'Procurement Material',
            'description' => 'Initial description',
        ])->assertRedirect(route('mm05.materials.index'));
        $material = MmMaterial::query()->where('code', 'MAT-PROC')->sole();
        $this->assertSame($procurement->id, $material->created_by);

        $this->put(route('mm05.materials.update', $material->id), [
            'category_id' => $activeCategory->id,
            'code' => 'MAT-PROC-UPDATED',
            'name' => 'Updated Procurement Material',
            'description' => 'Updated description',
        ])->assertRedirect(route('mm05.materials.index'));
        $this->assertDatabaseHas('mm_materials', [
            'id' => $material->id,
            'code' => 'MAT-PROC-UPDATED',
            'name' => 'Updated Procurement Material',
            'description' => 'Updated description',
            'updated_by' => $procurement->id,
        ]);
        $this->post(route('mm05.materials.deactivate', $material->id))
            ->assertRedirect(route('mm05.materials.index'));
        $this->assertDatabaseHas('mm_materials', ['id' => $material->id, 'is_active' => false]);
    }

    public function test_unit_usage_group_cannot_exclude_existing_base_or_price_references(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create([
            'code' => 'CAT-UOM-REF',
            'name' => 'UOM Reference Category',
        ]);
        $baseUnit = MmUnit::query()->create([
            'code' => 'UOM-BASE-REF',
            'name' => 'Base Reference Unit',
            'usage_group' => 'both',
        ]);
        $priceUnit = MmUnit::query()->create([
            'code' => 'UOM-PRICE-REF',
            'name' => 'Price Reference Unit',
            'usage_group' => 'both',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-UOM-REF',
            'name' => 'UOM Reference Material',
        ]);
        MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-UOM-REF',
            'variant_name' => 'UOM Reference Variant',
            'base_unit_id' => $baseUnit->id,
        ]);
        MmMaterialPrice::query()->create([
            'material_variant_id' => $material->variants()->sole()->id,
            'price_unit_id' => $priceUnit->id,
            'amount' => '100.0000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-01',
        ]);

        $this->actingAs($procurement)
            ->put(route('mm05.units.update', $baseUnit->id), [
                'usage_group' => 'price',
            ])
            ->assertSessionHasErrors('usage_group');

        $this->put(route('mm05.units.update', $priceUnit->id), [
            'usage_group' => 'base',
        ])->assertSessionHasErrors('usage_group');

        $this->assertDatabaseHas('mm_units', [
            'id' => $baseUnit->id,
            'usage_group' => 'both',
        ]);
        $this->assertDatabaseHas('mm_units', [
            'id' => $priceUnit->id,
            'usage_group' => 'both',
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $material->variants()->sole()->id,
            'price_unit_id' => $priceUnit->id,
            'amount' => '100.0000',
        ]);
    }

    public function test_procurement_creates_specification_only_units_and_preserves_codes_when_editing(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $this->actingAs($procurement)->post(route('mm05.units.store'), [
            'code' => 'USER-CODE',
            'name' => 'Millimeter',
            'dimension' => 'length',
            'usage_group' => '',
            'is_specification_unit' => '1',
            'is_available_for_new_material' => '0',
        ])->assertRedirect(route('mm05.units.index'));

        $unit = MmUnit::query()->where('name', 'Millimeter')->sole();
        $this->assertMatchesRegularExpression('/\AUOM-\d{3,}\z/', $unit->code);
        $this->assertNull($unit->usage_group);
        $this->assertTrue($unit->is_specification_unit);
        $this->assertFalse($unit->is_available_for_new_material);

        $this->put(route('mm05.units.update', $unit), [
            'code' => 'CHANGED-CODE',
            'name' => 'Millimeter Revised',
            'dimension' => 'length',
            'usage_group' => '',
            'is_specification_unit' => '1',
            'is_available_for_new_material' => '0',
        ])->assertRedirect(route('mm05.units.index'));

        $this->assertDatabaseHas('mm_units', [
            'id' => $unit->id,
            'code' => $unit->code,
            'name' => 'Millimeter Revised',
            'usage_group' => null,
            'is_specification_unit' => true,
            'is_available_for_new_material' => false,
        ]);
        $this->get(route('mm05.units.index'))
            ->assertOk()
            ->assertSee('Spesifikasi saja')
            ->assertDontSee('name="code"', false);
    }

    public function test_units_page_hides_code_column_and_renders_accessible_icon_actions(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $unit = MmUnit::query()->create([
            'code' => 'UOM-UI-'.Str::upper(Str::random(6)),
            'name' => 'Satuan Tampilan Panjang',
            'dimension' => 'length',
            'usage_group' => 'both',
            'is_specification_unit' => true,
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($procurement)->get(route('mm05.units.index'));

        $response->assertOk()
            ->assertSee('id="new-code" value="Otomatis oleh sistem" readonly', false)
            ->assertSee('id="new-unit-dialog"', false)
            ->assertSee('data-unit-dialog-open="new-unit-dialog"', false)
            ->assertSee('aria-label="Edit satuan '.$unit->name.'"', false)
            ->assertSee('title="Edit satuan"', false)
            ->assertSee('aria-label="Nonaktifkan satuan '.$unit->name.'"', false)
            ->assertSee('title="Nonaktifkan satuan"', false)
            ->assertSee('Nonaktifkan satuan ini? Data tidak akan dihapus.', false)
            ->assertSee('action="'.route('mm05.units.delete', $unit->id).'"', false)
            ->assertSee('aria-label="Hapus satuan '.$unit->name.' secara permanen"', false)
            ->assertSee('action="'.route('mm05.units.update', $unit->id).'"', false)
            ->assertSee('action="'.route('mm05.units.deactivate', $unit->id).'"', false);

        preg_match('/<table class="mm-table mm-unit-table">.*?<\/table>/s', $response->getContent(), $table);
        $this->assertArrayHasKey(0, $table);
        preg_match_all('/<th\b/', $table[0], $headers);

        $this->assertCount(7, $headers[0]);
        $this->assertStringNotContainsString('Kode', $table[0]);
        $this->assertStringNotContainsString($unit->code, $table[0]);
    }

    public function test_master_unit_form_creates_each_material_group_and_rejects_duplicate_names(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $this->actingAs($procurement)->get(route('mm05.units.index'))
            ->assertOk()
            ->assertSee('value="base"', false)
            ->assertSee('value="price"', false)
            ->assertSee('value="both"', false)
            ->assertSee('value="temperature"', false);

        foreach (['base', 'price', 'both'] as $usageGroup) {
            $this->post(route('mm05.units.store'), [
                'code' => 'CLIENT-CODE-'.$usageGroup,
                'name' => 'Unit '.$usageGroup,
                'dimension' => 'count',
                'usage_group' => $usageGroup,
                'is_available_for_new_material' => '1',
            ])->assertRedirect(route('mm05.units.index'));
        }

        $baseUnit = MmUnit::query()->where('name', 'Unit base')->sole();
        $priceUnit = MmUnit::query()->where('name', 'Unit price')->sole();
        $bothUnit = MmUnit::query()->where('name', 'Unit both')->sole();
        foreach ([$baseUnit, $priceUnit, $bothUnit] as $unit) {
            $this->assertMatchesRegularExpression('/\AUOM-\d{3,}\z/', $unit->code);
            $this->assertFalse(str_starts_with($unit->code, 'CLIENT-CODE-'));
        }

        $this->put(route('mm05.units.update', $baseUnit), [
            'name' => $baseUnit->name,
            'dimension' => 'count',
            'usage_group' => 'both',
            'is_available_for_new_material' => '1',
        ])->assertRedirect(route('mm05.units.index'));

        $this->assertDatabaseHas('mm_units', [
            'id' => $baseUnit->id,
            'code' => $baseUnit->code,
            'usage_group' => 'both',
        ]);

        $unitCount = MmUnit::query()->count();
        $duplicateResponse = $this->post(route('mm05.units.store'), [
            'name' => ' unit BASE ',
            'dimension' => 'count',
            'usage_group' => 'both',
            'is_available_for_new_material' => '1',
            'form_context' => 'create',
        ]);
        $duplicateResponse->assertSessionHasErrors('name');
        $this->get(route('mm05.units.index'))
            ->assertOk()
            ->assertSee('id="new-unit-dialog"', false)
            ->assertSee('data-unit-dialog-open="new-unit-dialog"', false)
            ->assertSee('document.getElementById(button.dataset.unitDialogOpen)?.showModal()', false);

        $this->assertSame($unitCount, MmUnit::query()->count());
    }

    public function test_procurement_creates_unit_through_the_add_modal_form(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $this->actingAs($procurement)->get(route('mm05.units.index'))
            ->assertOk()
            ->assertSee('data-unit-dialog-open="new-unit-dialog"', false)
            ->assertSee('aria-haspopup="dialog"', false)
            ->assertSee('action="'.route('mm05.units.store').'"', false)
            ->assertSee('name="usage_group"', false)
            ->assertSee('name="is_specification_unit"', false)
            ->assertSee('name="is_available_for_new_material"', false);

        $this->post(route('mm05.units.store'), [
            'form_context' => 'create',
            'name' => 'Modal Created Unit',
            'dimension' => 'length',
            'usage_group' => 'base',
            'is_specification_unit' => '1',
            'is_available_for_new_material' => '1',
        ])->assertRedirect(route('mm05.units.index'));

        $unit = MmUnit::query()->where('name', 'Modal Created Unit')->sole();
        $this->assertMatchesRegularExpression('/\AUOM-\d{3,}\z/', $unit->code);
        $this->assertDatabaseHas('mm_units', [
            'id' => $unit->id,
            'usage_group' => 'base',
            'is_specification_unit' => true,
            'is_available_for_new_material' => true,
        ]);
    }

    public function test_unit_can_be_permanently_deleted_when_it_has_no_references(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $unit = MmUnit::query()->create([
            'code' => 'UOM-DELETE-'.Str::upper(Str::random(6)),
            'name' => 'Unused Deletion Unit',
            'usage_group' => 'both',
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);
        $unrelatedUnit = MmUnit::query()->create([
            'code' => 'UOM-KEEP-'.Str::upper(Str::random(6)),
            'name' => 'Unrelated Kept Unit',
            'usage_group' => 'both',
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);

        $this->actingAs($procurement)
            ->from(route('mm05.units.index'))
            ->delete(route('mm05.units.delete', $unit))
            ->assertRedirect(route('mm05.units.index'))
            ->assertSessionHas('message', 'Satuan berhasil dihapus permanen.');

        $this->assertDatabaseMissing('mm_units', ['id' => $unit->id]);
        $this->assertDatabaseHas('mm_units', ['id' => $unrelatedUnit->id, 'code' => $unrelatedUnit->code]);
    }

    public function test_unit_deletion_is_blocked_with_all_reference_details(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create([
            'code' => 'CAT-DELETE-REF',
            'name' => 'Delete Reference Category',
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UOM-DELETE-REF',
            'name' => 'Referenced Deletion Unit',
            'usage_group' => 'both',
            'is_specification_unit' => true,
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);
        $otherUnit = MmUnit::query()->create([
            'code' => 'UOM-DELETE-OTHER',
            'name' => 'Other Conversion Unit',
            'usage_group' => 'both',
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-DELETE-REF',
            'name' => 'Delete Reference Material',
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-DELETE-REF',
            'variant_name' => 'Delete Reference Variant',
            'base_unit_id' => $unit->id,
        ]);
        MmMaterialUnitConversion::query()->create([
            'material_id' => $material->id,
            'base_unit_id' => $unit->id,
            'price_unit_id' => $otherUnit->id,
            'conversion_factor' => '100.00000000',
        ]);
        MmMaterialUnitConversion::query()->create([
            'material_id' => $material->id,
            'base_unit_id' => $otherUnit->id,
            'price_unit_id' => $unit->id,
            'conversion_factor' => '100.00000000',
        ]);
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-DELETE-REF',
            'name' => 'Delete Reference Supplier',
        ]);
        $quote = MmSupplierQuote::query()->create([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'amount' => '60000.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-15',
        ]);
        MmMaterialPrice::query()->create([
            'material_variant_id' => $variant->id,
            'price_unit_id' => $unit->id,
            'source_quote_id' => $quote->id,
            'price_type' => 'reference',
            'amount' => '60000.0000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-15',
        ]);
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => 'ATTR-DELETE-REF',
            'label' => 'Referenced Dimension',
            'data_type' => 'number',
            'unit_id' => $unit->id,
            'is_required' => false,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $template = MmSpecificationTemplate::query()->create([
            'category_id' => $category->id,
            'code' => 'TPL-DELETE-REF',
            'name' => 'Delete Reference Template',
            'is_active' => true,
        ]);
        MmSpecificationTemplateAttribute::query()->create([
            'template_id' => $template->id,
            'attribute_definition_id' => $definition->id,
            'is_required' => false,
            'display_order' => 0,
            'input_type' => 'number',
            'unit_id' => $unit->id,
            'unit_id_is_explicit' => true,
        ]);

        $this->actingAs($procurement)
            ->delete(route('mm05.units.delete', $unit))
            ->assertRedirect(route('mm05.units.index'))
            ->assertSessionHasErrors('unit');

        $response = $this->get(route('mm05.units.index'));
        $response->assertOk();
        foreach ([
            'varian material (satuan dasar): 1',
            'konversi material (satuan dasar): 1',
            'konversi material (satuan harga): 1',
            'riwayat harga material: 1',
            'penawaran harga supplier: 1',
            'definisi atribut spesifikasi: 1',
            'assignment template spesifikasi: 1',
        ] as $referenceDetail) {
            $response->assertSee($referenceDetail);
        }

        $this->assertDatabaseHas('mm_units', [
            'id' => $unit->id,
            'code' => 'UOM-DELETE-REF',
        ]);
        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'price_unit_id' => $unit->id,
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'source_quote_id' => $quote->id,
            'price_unit_id' => $unit->id,
        ]);
    }

    public function test_units_pagination_shows_ten_records_and_preserves_filters(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        for ($number = 1; $number <= 11; $number++) {
            MmUnit::query()->create([
                'code' => 'UOM-PAGE-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'name' => 'Pagination Filter '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'usage_group' => 'both',
                'is_available_for_new_material' => true,
                'is_active' => true,
            ]);
        }
        MmUnit::query()->create([
            'code' => 'UOM-PAGE-INACTIVE',
            'name' => 'Pagination Filter Inactive',
            'usage_group' => 'both',
            'is_available_for_new_material' => true,
            'is_active' => false,
        ]);

        $firstPage = $this->actingAs($procurement)->get(route('mm05.units.index', [
            'q' => 'Pagination Filter',
            'status' => 'active',
        ]));
        $firstPage->assertOk()
            ->assertSee('Menampilkan 1–10 dari 11 data')
            ->assertSee('Halaman 1 dari 2')
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('aria-current="page">1', false)
            ->assertSee('Pagination Filter 01')
            ->assertSee('Pagination Filter 10')
            ->assertDontSee('Pagination Filter 11');
        $this->assertStringContainsString('q=Pagination%20Filter', $firstPage->getContent());
        $this->assertStringContainsString('status=active', $firstPage->getContent());
        $this->assertStringContainsString('page=2', $firstPage->getContent());

        preg_match('/<tbody>.*?<\/tbody>/s', $firstPage->getContent(), $body);
        $this->assertArrayHasKey(0, $body);
        preg_match_all('/<tr\b/', $body[0], $rows);
        $this->assertCount(10, $rows[0]);

        $secondPage = $this->get(route('mm05.units.index', [
            'q' => 'Pagination Filter',
            'status' => 'active',
            'page' => 2,
        ]));
        $secondPage->assertOk()
            ->assertSee('Menampilkan 11–11 dari 11 data')
            ->assertSee('Halaman 2 dari 2')
            ->assertSee('Pagination Filter 11')
            ->assertDontSee('Pagination Filter Inactive')
            ->assertSee('aria-current="page">2', false);
    }

    public function test_specification_usage_cannot_be_disabled_while_a_definition_references_the_unit(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create([
            'code' => 'CAT-UOM-SPEC-REF',
            'name' => 'Specification Unit Reference',
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UOM-SPEC-REF',
            'name' => 'Referenced Specification Unit',
            'usage_group' => 'both',
            'is_specification_unit' => true,
            'is_available_for_new_material' => true,
        ]);
        MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => 'ATTR-UOM-SPEC-REF',
            'label' => 'Panjang',
            'data_type' => 'number',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $this->actingAs($procurement)->put(route('mm05.units.update', $unit), [
            'usage_group' => 'both',
            'is_specification_unit' => '0',
            'is_available_for_new_material' => '1',
        ])->assertSessionHasErrors('is_specification_unit');

        $this->assertDatabaseHas('mm_units', [
            'id' => $unit->id,
            'is_specification_unit' => true,
        ]);
        $this->assertDatabaseHas('mm_attribute_definitions', [
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_existing_material_variant_keeps_its_specification_only_base_unit_editable(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create([
            'code' => 'CAT-UOM-LEGACY',
            'name' => 'Legacy UOM Category',
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UOM-LEGACY-CM',
            'name' => 'cm',
            'usage_group' => 'both',
            'is_specification_unit' => true,
            'is_available_for_new_material' => false,
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-UOM-LEGACY',
            'name' => 'Legacy Unit Material',
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-UOM-LEGACY',
            'variant_name' => 'Legacy Variant',
            'base_unit_id' => $unit->id,
        ]);

        $this->actingAs($procurement)->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee('value="'.$unit->id.'" selected', false)
            ->assertSee('cm (satuan lama; dipertahankan)');

        $this->put(route('mm05.variants.update', $variant), [
            'material_id' => $material->id,
            'sku' => $variant->sku,
            'variant_name' => 'Updated Legacy Variant',
            'base_unit_id' => $unit->id,
            'specification_note' => 'Existing material remains editable',
        ])->assertRedirect(route('mm05.variants.index'));

        $this->assertDatabaseHas('mm_material_variants', [
            'id' => $variant->id,
            'base_unit_id' => $unit->id,
            'variant_name' => 'Updated Legacy Variant',
        ]);
    }

    public function test_procurement_manages_flat_categories_without_parent_controls(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $this->actingAs($procurement)->get(route('mm05.categories.index'))
            ->assertOk()
            ->assertDontSee('Kategori induk')
            ->assertDontSee('name="parent_id"', false);

        $this->post(route('mm05.categories.store'), [
            'name' => 'Created Flat Category',
            'parent_id' => 999999,
        ])->assertRedirect(route('mm05.categories.index'));
        $category = MmCategory::query()->where('name', 'Created Flat Category')->sole();
        $this->assertMatchesRegularExpression('/\\ACAT-\\d{3,}\\z/', $category->code);
        $this->assertNull($category->parent_id);

        $this->put(route('mm05.categories.update', $category->id), [
            'name' => 'Renamed Flat Category',
            'parent_id' => 888888,
        ])->assertRedirect(route('mm05.categories.index'));
        $this->assertDatabaseHas('mm_categories', [
            'id' => $category->id,
            'name' => 'Renamed Flat Category',
            'parent_id' => null,
        ]);
    }

    public function test_material_category_cannot_change_after_variant_specifications_are_saved(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create(['code' => 'CAT-SPEC', 'name' => 'Specification Category']);
        $otherCategory = MmCategory::query()->create(['code' => 'CAT-OTHER', 'name' => 'Other Category']);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-SPEC',
            'name' => 'Specification Unit',
            'dimension' => 'length',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-SPEC',
            'name' => 'Specification Material',
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-SPEC',
            'variant_name' => 'Specification Variant',
            'base_unit_id' => $unit->id,
        ]);
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => 'ATTR-SPEC',
            'label' => 'Specification',
            'data_type' => 'text',
        ]);
        $variant->attributes()->create([
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Existing specification value',
        ]);

        $this->actingAs($procurement)
            ->put(route('mm05.materials.update', $material), [
                'category_id' => $otherCategory->id,
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseHas('mm_materials', [
            'id' => $material->id,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Existing specification value',
        ]);
        $this->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee('Existing specification value');
    }

    public function test_master_codes_are_unique_and_new_records_reject_inactive_references(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $suffix = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 12));
        $category = MmCategory::query()->create([
            'code' => 'CAT-'.$suffix,
            'name' => 'Category '.$suffix,
        ]);
        $inactiveCategory = MmCategory::query()->create([
            'code' => 'CAT-OFF-'.$suffix,
            'name' => 'Inactive Category '.$suffix,
            'is_active' => false,
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-'.$suffix,
            'name' => 'Unit '.$suffix,
            'dimension' => 'count',
        ]);
        $inactiveUnit = MmUnit::query()->create([
            'code' => 'UNIT-OFF-'.$suffix,
            'name' => 'Inactive Unit '.$suffix,
            'dimension' => 'count',
            'is_active' => false,
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-'.$suffix,
            'name' => 'Material '.$suffix,
        ]);

        $this->actingAs($procurement)
            ->post(route('mm05.categories.store'), [
                'code' => $category->code,
                'name' => 'Category With Ignored Client Code',
            ])
            ->assertRedirect(route('mm05.categories.index'));
        $generatedCategory = MmCategory::query()->where('name', 'Category With Ignored Client Code')->sole();
        $this->assertNotSame($category->code, $generatedCategory->code);

        $this->post(route('mm05.units.store'), [
            'code' => $unit->code,
            'name' => 'Duplicate Unit',
            'dimension' => 'count',
            'usage_group' => 'both',
        ])->assertRedirect(route('mm05.units.index'));

        $generatedUnit = MmUnit::query()
            ->where('name', 'Duplicate Unit')
            ->sole();

        $this->assertNotSame($unit->code, $generatedUnit->code);
        $this->assertMatchesRegularExpression('/\\AUOM-\\d{3,}\\z/', $generatedUnit->code);

        $this->post(route('mm05.units.store'), [
            'code' => 'UNIT-BAD-'.$suffix,
            'name' => 'Invalid Dimension',
            'dimension' => 'countable',
            'usage_group' => 'both',
        ])->assertSessionHasErrors('dimension');

        $this->post(route('mm05.materials.store'), [
            'category_id' => $category->id,
            'code' => $material->code,
            'name' => 'Duplicate Material',
        ])->assertSessionHasErrors('code');

        $this->post(route('mm05.materials.store'), [
            'category_id' => $inactiveCategory->id,
            'code' => 'MAT-OFF-'.$suffix,
            'name' => 'Inactive Category Material',
        ])->assertSessionHasErrors('category_id');

        $this->post(route('mm05.variants.store'), [
            'material_id' => $material->id,
            'sku' => 'SKU-OFF-'.$suffix,
            'variant_name' => 'Inactive Unit Variant',
            'base_unit_id' => $inactiveUnit->id,
        ])->assertSessionHasErrors('base_unit_id');

        $this->assertDatabaseMissing('mm_materials', ['code' => 'MAT-OFF-'.$suffix]);
        $this->assertDatabaseMissing('mm_material_variants', ['sku' => 'SKU-OFF-'.$suffix]);
    }

    public function test_unauthenticated_user_is_redirected_and_unlisted_role_is_forbidden(): void
    {
        $this->get(route('mm05.workspace'))->assertRedirect(route('login'));

        $designer = $this->createUserWithRole('DESIGNER');
        $this->actingAs($designer)->get(route('mm05.workspace'))->assertForbidden();
        $this->get(route('mm05.suppliers.index'))->assertForbidden();
        $this->post(route('mm05.categories.store'), [
            'code' => 'CAT-DESIGNER',
            'name' => 'Designer Category',
        ])->assertForbidden();
        $this->post(route('mm05.suppliers.store'), [])->assertForbidden();

        $unconfirmedRole = $this->createUserWithRole('ASSISTANT_MANAGER_PRODUKSI');
        $this->actingAs($unconfirmedRole)->get(route('mm05.materials.index'))->assertForbidden();
    }

    public function test_existing_production_read_roles_can_view_but_cannot_manage(): void
    {
        $supplier = MmSupplier::query()->create([
            'code' => 'SUP-READONLY',
            'name' => 'Read-only Supplier',
        ]);

        foreach (['ADMIN_BPM', 'ADMIN_PRODUKSI', 'ASST_MANAGER_PRODUKSI'] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);

            $this->actingAs($user)->get(route('mm05.workspace'))->assertOk();
            $this->get(route('mm05.materials.index'))
                ->assertOk()
                ->assertDontSee('method="POST" action="'.route('mm05.materials.store').'"', false);
            $this->get(route('mm05.suppliers.index'))
                ->assertOk()
                ->assertSee('Read-only Supplier')
                ->assertDontSee('method="POST" action="'.route('mm05.suppliers.store').'"', false)
                ->assertDontSee(route('mm05.suppliers.update', $supplier))
                ->assertDontSee(route('mm05.suppliers.deactivate', $supplier))
                ->assertDontSee(route('mm05.suppliers.activate', $supplier));

            $this->post(route('mm05.suppliers.store'), [
                'name' => 'Unauthorized Supplier',
            ])->assertForbidden();
            $this->put(route('mm05.suppliers.update', $supplier), [
                'name' => 'Changed Supplier',
            ])->assertForbidden();
            $this->post(route('mm05.suppliers.deactivate', $supplier))->assertForbidden();
            $this->post(route('mm05.suppliers.activate', $supplier))->assertForbidden();
        }

        $this->assertDatabaseHas('mm_suppliers', [
            'id' => $supplier->id,
            'code' => 'SUP-READONLY',
            'name' => 'Read-only Supplier',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('mm_suppliers', ['name' => 'Unauthorized Supplier']);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $user = User::query()->create([
            'name' => $roleCode.' User',
            'email' => strtolower($roleCode).'@mm.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => str_replace('_', ' ', $roleCode)],
        );
        $user->roles()->attach($role);

        return $user;
    }
}
