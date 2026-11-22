<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MmCategoryAndFlexibleSpecificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_six_recommended_categories_are_available_without_changing_existing_test_categories(): void
    {
        $testCategory = MmCategory::query()->firstOrCreate(
            ['code' => 'TEST-MM02'],
            ['name' => 'Kategori Pengujian MM02', 'parent_id' => null, 'is_active' => true],
        );
        $testCategoryName = $testCategory->name;
        $existingDefault = MmCategory::query()->create([
            'code' => 'CAT-OLD-001',
            'name' => 'Bahan Baku Produksi',
        ]);
        $existingMaterial = MmMaterial::query()->create([
            'category_id' => $existingDefault->id,
            'code' => 'MAT-KEEP-001',
            'name' => 'Existing material remains linked',
        ]);
        $migration = require database_path('migrations/2026_11_12_170000_add_mm05_default_categories_and_recommended_attributes.php');
        $migration->up();
        $migration->up();

        $defaults = [
            'CAT-001' => 'Bahan Baku Produksi',
            'CAT-002' => 'Barang Pendukung Produksi',
            'CAT-003' => 'Bahan Kemasan',
            'CAT-004' => 'Sparepart / Suku Cadang Mesin',
            'CAT-005' => 'Barang Operasional Kantor',
            'CAT-006' => 'Barang Jadi / Produk',
        ];

        foreach ($defaults as $code => $name) {
            $this->assertDatabaseHas('mm_categories', ['code' => $code, 'name' => $name, 'is_active' => true]);
        }

        $recommendations = [
            'CAT-001' => ['Grade', 'Ukuran', 'Ketebalan', 'Panjang', 'Lebar', 'Berat', 'Warna'],
            'CAT-002' => ['Jenis', 'Merek', 'Model', 'Ukuran', 'Spesifikasi teknis'],
            'CAT-003' => ['Jenis bahan', 'Panjang', 'Lebar', 'Ketebalan', 'Warna', 'Kapasitas'],
            'CAT-004' => ['Merek', 'Model', 'Tipe', 'Nomor part', 'Ukuran', 'Kompatibilitas mesin'],
            'CAT-005' => ['Merek', 'Model', 'Ukuran', 'Tipe', 'Warna'],
            'CAT-006' => ['Model', 'Tipe', 'Ukuran', 'Warna', 'Berat', 'Kemasan'],
        ];
        foreach ($recommendations as $categoryCode => $labels) {
            $category = MmCategory::query()->where('code', $categoryCode)->sole();
            foreach ($labels as $label) {
                $this->assertDatabaseHas('mm_attribute_definitions', [
                    'category_id' => $category->id,
                    'label' => $label,
                    'is_required' => false,
                ]);
            }
        }

        $this->assertDatabaseHas('mm_categories', ['code' => 'TEST-MM02', 'name' => $testCategoryName]);
        $this->assertDatabaseHas('mm_categories', ['id' => $existingDefault->id, 'code' => 'CAT-OLD-001']);
        $this->assertDatabaseHas('mm_materials', ['id' => $existingMaterial->id, 'category_id' => $existingDefault->id]);
        $this->assertSame(6, MmCategory::query()->whereIn('code', array_keys($defaults))->count());
    }

    public function test_category_code_is_generated_and_category_can_be_renamed_and_deactivated_without_parent_fields(): void
    {
        $user = $this->createProcurement();
        $response = $this->actingAs($user)->get(route('mm05.categories.index'));
        $response->assertOk()->assertDontSee('Kategori induk')->assertDontSee('name="parent_id"', false);

        $this->post(route('mm05.categories.store'), ['name' => 'Kategori Baru'])->assertRedirect(route('mm05.categories.index'));
        $category = MmCategory::query()->where('name', 'Kategori Baru')->sole();
        $this->assertMatchesRegularExpression('/\ACAT-\d{3,}\z/', $category->code);
        $this->assertNull($category->parent_id);

        $this->put(route('mm05.categories.update', $category), [
            'name' => 'Kategori Diperbarui',
            'parent_id' => '999999',
        ])->assertRedirect(route('mm05.categories.index'));
        $this->assertDatabaseHas('mm_categories', [
            'id' => $category->id,
            'code' => $category->code,
            'name' => 'Kategori Diperbarui',
            'parent_id' => null,
        ]);

        $this->post(route('mm05.categories.deactivate', $category))->assertRedirect(route('mm05.categories.index'));
        $this->assertDatabaseHas('mm_categories', ['id' => $category->id, 'is_active' => false]);
    }

    public function test_wizard_allows_blank_recommended_attributes_and_saves_custom_attributes_for_detail_editing(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $recommended = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'GRADE-'.$catalog['suffix'],
            'label' => 'Grade',
            'data_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), [
            'category_id' => $catalog['category']->id,
            'name' => 'Flexible Material '.$catalog['suffix'],
            'base_unit_id' => $catalog['unit']->id,
            'attributes' => [$recommended->id => ''],
            'custom_attributes' => [
                ['name' => 'Finishing', 'value' => '2B'],
                ['name' => 'Lot khusus', 'value' => 'L-2026'],
            ],
            'supplier_mode' => 'none',
        ])->assertRedirect();

        $material = MmMaterial::query()->where('name', 'Flexible Material '.$catalog['suffix'])->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $finishing = MmAttributeDefinition::query()->where('category_id', $catalog['category']->id)->where('label', 'Finishing')->sole();
        $lot = MmAttributeDefinition::query()->where('category_id', $catalog['category']->id)->where('label', 'Lot khusus')->sole();

        $this->assertFalse($finishing->is_required);
        $this->assertFalse($lot->is_required);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $finishing->id,
            'value_text' => '2B',
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $lot->id,
            'value_text' => 'L-2026',
        ]);

        $this->actingAs($user)->get(route('mm05.materials.show', $material->id))
            ->assertOk()->assertSee('Finishing')->assertSee('2B')->assertSee('Lot khusus')->assertSee('L-2026');

        $this->put(route('mm05.materials.variants.attributes.update', [$material->id, $variant->id]), [
            'attributes' => [
                ['attribute_definition_id' => $finishing->id, 'value_text' => 'No. 4'],
                ['attribute_definition_id' => $lot->id, 'value_text' => 'L-2027'],
            ],
        ])->assertRedirect(route('mm05.materials.show', $material->id));

        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $finishing->id,
            'value_text' => 'No. 4',
        ]);
        $this->actingAs($user)->get(route('mm05.materials.show', $material->id))
            ->assertOk()->assertSee('No. 4')->assertSee('L-2027');
    }

    public function test_wizard_ignores_empty_custom_attribute_rows_and_accepts_empty_recommended_attributes(): void
    {
        $user = $this->createProcurement();
        $catalog = $this->createCatalog();
        $definition = MmAttributeDefinition::query()->create([
            'category_id' => $catalog['category']->id,
            'code' => 'SIZE-'.$catalog['suffix'],
            'label' => 'Ukuran',
            'data_type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), [
            'category_id' => $catalog['category']->id,
            'name' => 'Optional Material '.$catalog['suffix'],
            'base_unit_id' => $catalog['unit']->id,
            'attributes' => [$definition->id => ''],
            'custom_attributes' => [['name' => '', 'value' => '']],
            'supplier_mode' => 'none',
        ])->assertRedirect();

        $material = MmMaterial::query()->where('name', 'Optional Material '.$catalog['suffix'])->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        $this->assertDatabaseMissing('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
        ]);
        $this->assertDatabaseMissing('mm_attribute_definitions', [
            'category_id' => $catalog['category']->id,
            'label' => '',
        ]);
    }

    private function createCatalog(): array
    {
        $suffix = Str::upper(Str::random(8));
        $category = MmCategory::query()->create(['code' => 'CAT-FLEX-'.$suffix, 'name' => 'Flexible '.$suffix]);
        $unit = MmUnit::query()->create(['code' => 'UNIT-FLEX-'.$suffix, 'name' => 'Unit '.$suffix, 'dimension' => 'count']);

        return compact('suffix', 'category', 'unit');
    }

    private function createProcurement(): User
    {
        $suffix = Str::lower(Str::random(10));
        $user = User::query()->create([
            'name' => 'MM05 Procurement '.$suffix,
            'email' => $suffix.'@mm05-category.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(['code' => 'PROCUREMENT'], ['name' => 'Procurement']);
        $user->roles()->attach($role);

        return $user;
    }
}
