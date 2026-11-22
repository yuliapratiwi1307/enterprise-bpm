<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmSpecificationTemplate;
use App\Models\MmUnit;
use App\Models\User;
use App\Services\MmAttributeDefinitionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class Mm05StainlessSteelFlexibleSpecificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stainless_steel_material_saves_and_updates_recommended_and_custom_specifications(): void
    {
        $migration = require database_path('migrations/2026_11_12_170000_add_mm05_default_categories_and_recommended_attributes.php');
        $migration->up();

        $category = MmCategory::query()->where('code', 'CAT-001')->sole();
        $this->assertSame('Bahan Baku Produksi', $category->name);

        $definitions = MmAttributeDefinition::query()
            ->where('category_id', $category->id)
            ->whereIn('label', ['Grade', 'Ketebalan', 'Lebar', 'Panjang'])
            ->get()
            ->keyBy('label');
        $this->assertCount(4, $definitions);
        $finishing = app(MmAttributeDefinitionService::class)->findOrCreateFlexibleDefinition((int) $category->id, 'Finishing');
        $template = MmSpecificationTemplate::query()->create([
            'category_id' => $category->id,
            'code' => 'TMP-SS304-'.Str::upper(Str::random(8)),
            'name' => 'Stainless Steel '.Str::upper(Str::random(5)),
            'is_active' => true,
        ]);
        $templateDefinitions = array_values([...$definitions->all(), $finishing]);
        foreach ($templateDefinitions as $index => $definition) {
            $template->templateAttributes()->create([
                'attribute_definition_id' => $definition->id,
                'is_required' => false,
                'display_order' => $index + 1,
                'input_type' => $definition->data_type,
                'unit_id' => $definition->unit_id,
                'unit_id_is_explicit' => $definition->unit_id !== null,
            ]);
        }

        $unit = MmUnit::query()->create([
            'code' => 'UNIT-SS304-'.Str::upper(Str::random(8)),
            'name' => 'Unit SS304 '.Str::upper(Str::random(5)),
            'dimension' => 'count',
        ]);
        $user = $this->createProcurement();

        $this->actingAs($user)->post(route('mm05.materials.wizard.store'), [
            'category_id' => $category->id,
            'specification_template_id' => $template->id,
            'name' => 'Stainless Steel SS304',
            'base_unit_id' => $unit->id,
            'attributes' => [
                $definitions['Grade']->id => '304',
                $definitions['Ketebalan']->id => '2 mm',
                $definitions['Lebar']->id => '1.220 mm',
                $definitions['Panjang']->id => '2.440 mm',
                $finishing->id => '2B',
            ],
            'supplier_mode' => 'none',
        ])->assertRedirect();

        $material = MmMaterial::query()->where('name', 'Stainless Steel SS304')->sole();
        $variant = MmMaterialVariant::query()->where('material_id', $material->id)->sole();
        foreach ([
            'Grade' => '304',
            'Ketebalan' => '2 mm',
            'Lebar' => '1.220 mm',
            'Panjang' => '2.440 mm',
            'Finishing' => '2B',
        ] as $label => $value) {
            $definition = $label === 'Finishing' ? $finishing : $definitions[$label];
            $this->assertDatabaseHas('mm_variant_attributes', [
                'variant_id' => $variant->id,
                'attribute_definition_id' => $definition->id,
                'value_text' => $value,
            ]);
        }

        $this->actingAs($user)->get(route('mm05.materials.show', $material->id))
            ->assertOk()
            ->assertSee('Stainless Steel SS304')
            ->assertSee('Grade')->assertSee('304')
            ->assertSee('Ketebalan')->assertSee('2 mm')
            ->assertSee('Lebar')->assertSee('1.220 mm')
            ->assertSee('Panjang')->assertSee('2.440 mm')
            ->assertSee('Finishing')->assertSee('2B');

        $this->put(route('mm05.materials.variants.attributes.update', [$material->id, $variant->id]), [
            'attributes' => [
                ['attribute_definition_id' => $definitions['Grade']->id, 'value_text' => 'SS304'],
                ['attribute_definition_id' => $definitions['Ketebalan']->id, 'value_text' => '3 mm'],
                ['attribute_definition_id' => $definitions['Lebar']->id, 'value_text' => '1.220 mm'],
                ['attribute_definition_id' => $definitions['Panjang']->id, 'value_text' => '2.440 mm'],
                ['attribute_definition_id' => $finishing->id, 'value_text' => 'No. 4'],
            ],
        ])->assertRedirect(route('mm05.materials.show', $material->id));

        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definitions['Ketebalan']->id,
            'value_text' => '3 mm',
        ]);
        $this->assertDatabaseHas('mm_variant_attributes', [
            'variant_id' => $variant->id,
            'attribute_definition_id' => $finishing->id,
            'value_text' => 'No. 4',
        ]);
        $this->actingAs($user)->get(route('mm05.materials.show', $material->id))
            ->assertOk()->assertSee('3 mm')->assertSee('No. 4');
    }

    private function createProcurement(): User
    {
        $suffix = Str::lower(Str::random(10));
        $user = User::query()->create([
            'name' => 'MM05 E2E Procurement '.$suffix,
            'email' => $suffix.'@mm05-e2e.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(['code' => 'PROCUREMENT'], ['name' => 'Procurement']);
        $user->roles()->attach($role);

        return $user;
    }
}
