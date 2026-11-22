<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmSpecificationTemplate;
use App\Models\MmSpecificationTemplateAttribute;
use App\Models\MmUnit;
use App\Models\MmVariantAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MmSpecificationTemplateManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_specification_units_are_available_to_template_attributes(): void
    {
        $requiredUnits = [
            'mm',
            'cm',
            'inch',
            'km',
            'micrometer',
            'm²',
            'm³',
            'derajat celsius',
            'volt',
            'watt',
            'ampere',
        ];

        foreach ($requiredUnits as $name) {
            $units = MmUnit::query()
                ->whereRaw('LOWER(BTRIM(name)) = ?', [$name])
                ->get();

            $this->assertCount(1, $units, "Expected exactly one specification unit named {$name}.");
            $this->assertTrue($units->first()->is_specification_unit);
            $this->assertFalse($units->first()->is_available_for_new_material);
        }

        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'CT-0001', 'Cat');
        $volt = MmUnit::query()->whereRaw('LOWER(BTRIM(name)) = ?', ['volt'])->sole();

        $response = $this->actingAs($user)->get(route('mm05.templates.index', [
            'edit_template' => $template->id,
        ]));

        $response->assertOk()->assertSee($volt->name.' ('.$volt->code.')');
    }

    public function test_index_renders_a_four_column_template_list_and_add_modal_without_a_code_input(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'CT-0001', 'Cat');

        $response = $this->actingAs($user)->get(route('mm05.templates.index'));

        $response->assertOk()
            ->assertSee('Kode')
            ->assertSee('Nama Template')
            ->assertSee('Kategori')
            ->assertSee('Action')
            ->assertSee('Tambah Template')
            ->assertSee('addTemplateModal')
            ->assertSee('editTemplate'.$template->id)
            ->assertDontSee('name="code"', false);

        preg_match('/<table class="template-table" id="template-list">.*?<\/table>/s', $response->getContent(), $table);
        $this->assertArrayHasKey(0, $table);
        preg_match_all('/<th\b/', $table[0], $headers);
        $this->assertCount(4, $headers[0]);
    }

    public function test_template_codes_are_generated_uniquely_and_existing_codes_cannot_be_changed(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $legacyCat = $this->createTemplate($category, 'CT-0001', 'Cat');
        $legacyMultiplex = $this->createTemplate($category, 'MP-0001', 'Multiplex');

        $this->actingAs($user)->post(route('mm05.templates.store'), [
            'name' => 'Template Baru Satu',
            'category_id' => $category->id,
            'description' => 'Deskripsi opsional',
        ])->assertRedirect(route('mm05.templates.index'));

        $first = MmSpecificationTemplate::query()->where('name', 'Template Baru Satu')->sole();

        $this->post(route('mm05.templates.store'), [
            'name' => 'Template Baru Dua',
            'category_id' => $category->id,
        ])->assertRedirect(route('mm05.templates.index'));

        $second = MmSpecificationTemplate::query()->where('name', 'Template Baru Dua')->sole();

        $this->assertMatchesRegularExpression('/\ATPL-\d{6}\z/', $first->code);
        $this->assertMatchesRegularExpression('/\ATPL-\d{6}\z/', $second->code);
        $this->assertNotSame($first->code, $second->code);

        $this->put(route('mm05.templates.update', $legacyCat), [
            'name' => 'Cat Updated',
            'code' => 'CHANGED',
        ])->assertSessionHasErrors('code');

        $this->assertSame('CT-0001', $legacyCat->fresh()->code);
        $this->assertSame('MP-0001', $legacyMultiplex->fresh()->code);

        $this->post(route('mm05.templates.store'), [
            'name' => 'Manual Code',
            'category_id' => $category->id,
            'code' => 'USER-CODE',
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('mm_specification_templates', ['name' => 'Manual Code']);
    }

    public function test_edit_modals_show_only_the_selected_template_attributes(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $cat = $this->createTemplate($category, 'CT-0001', 'Cat');
        $multiplex = $this->createTemplate($category, 'MP-0001', 'Multiplex');

        $catLabels = ['Merek', 'Jenis Cat', 'Warna', 'Kode Warna', 'Finishing', 'Penggunaan', 'Volume', 'Basis Cat'];
        $multiplexLabels = ['Grade', 'Ketebalan', 'Panjang', 'Lebar', 'Jenis Kayu', 'Finishing'];
        $volumeUnit = $this->createUnit('L', 'Liter');

        foreach ($catLabels as $index => $label) {
            $definition = $this->createDefinition(
                $category,
                $label,
                $label === 'Volume' ? 'number' : 'text',
                $label === 'Volume' ? $volumeUnit : null,
                $index,
            );
            $this->attachDefinition($cat, $definition, $index, $label === 'Volume' ? $volumeUnit : null);
        }

        foreach ($multiplexLabels as $index => $label) {
            $definition = $this->createDefinition(
                $category,
                $label,
                in_array($label, ['Ketebalan', 'Panjang', 'Lebar'], true) ? 'number' : 'text',
                null,
                $index + count($catLabels),
            );
            $this->attachDefinition($multiplex, $definition, $index);
        }

        $response = $this->actingAs($user)->get(route('mm05.templates.index', [
            'edit_template' => $cat->id,
        ]));
        $response->assertOk();

        $catTable = $this->attributeTableContent($response->getContent(), $cat->id);
        $multiplexTable = $this->attributeTableContent($response->getContent(), $multiplex->id);
        preg_match_all('/<th\b/', $catTable, $headers);
        $this->assertCount(7, $headers[0]);
        $this->assertStringContainsString('draggable="true"', $catTable);
        $this->assertStringContainsString('data-display-order', $catTable);

        foreach ($catLabels as $label) {
            $this->assertStringContainsString($label, $catTable);
        }
        $this->assertStringNotContainsString('Jenis Kayu', $catTable);
        $this->assertStringNotContainsString('Grade', $catTable);
        preg_match_all('/<tr\b/', $catTable, $catRows);
        $this->assertCount(9, $catRows[0]);

        foreach ($multiplexLabels as $label) {
            $this->assertStringContainsString($label, $multiplexTable);
        }
        $this->assertStringNotContainsString('Merek', $multiplexTable);
        $this->assertStringNotContainsString('Jenis Cat', $multiplexTable);
        preg_match_all('/<tr\b/', $multiplexTable, $multiplexRows);
        $this->assertCount(7, $multiplexRows[0]);
    }

    public function test_template_attribute_assignments_can_be_added_updated_and_removed_without_deleting_definitions(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $unit = $this->createUnit('MM', 'Millimeter');
        $cat = $this->createTemplate($category, 'CT-0001', 'Cat');
        $multiplex = $this->createTemplate($category, 'MP-0001', 'Multiplex');
        $textDefinition = $this->createDefinition($category, 'Warna', 'text');
        $numberDefinition = $this->createDefinition($category, 'Volume', 'number', $unit);
        $multiplexDefinition = $this->createDefinition($category, 'Grade', 'text');
        $textAssignment = $this->attachDefinition($cat, $textDefinition, 0);
        $numberAssignment = $this->attachDefinition($cat, $numberDefinition, 1);
        $multiplexAssignment = $this->attachDefinition($multiplex, $multiplexDefinition, 0);

        $newDefinition = $this->createDefinition($category, 'Finishing', 'text');
        $this->actingAs($user)->post(route('mm05.templates.attributes.attach', $cat), [
            'attribute_definition_id' => $newDefinition->id,
            'is_required' => '1',
            'input_type' => 'text',
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $cat->id]));

        $newAssignment = MmSpecificationTemplateAttribute::query()
            ->where('template_id', $cat->id)
            ->where('attribute_definition_id', $newDefinition->id)
            ->sole();
        $this->assertNull($newAssignment->unit_id);
        $this->assertFalse($newAssignment->unit_id_is_explicit);

        $this->put(route('mm05.templates.update', $cat), [
            'name' => 'Cat Updated',
            'attributes' => [
                $textAssignment->id => [
                    'id' => $textAssignment->id,
                    'is_required' => '1',
                    'display_order' => 3,
                    'input_type' => 'number',
                    'unit_id' => '',
                ],
                $numberAssignment->id => [
                    'id' => $numberAssignment->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'number',
                    'unit_id' => $unit->id,
                ],
                $newAssignment->id => [
                    'id' => $newAssignment->id,
                    'is_required' => '1',
                    'display_order' => 2,
                    'input_type' => 'text',
                    'unit_id' => '',
                ],
            ],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $cat->id]));

        $this->assertDatabaseHas('mm_specification_template_attributes', [
            'id' => $textAssignment->id,
            'is_required' => true,
            'display_order' => 3,
            'input_type' => 'number',
            'unit_id' => null,
        ]);
        $this->assertDatabaseHas('mm_specification_template_attributes', [
            'id' => $numberAssignment->id,
            'is_required' => false,
            'display_order' => 1,
            'input_type' => 'number',
            'unit_id' => $unit->id,
        ]);

        $this->delete(route('mm05.templates.attributes.detach', [$cat, $newAssignment]))
            ->assertRedirect(route('mm05.templates.index', ['edit_template' => $cat->id]));

        $this->assertDatabaseMissing('mm_specification_template_attributes', ['id' => $newAssignment->id]);
        $this->assertDatabaseHas('mm_attribute_definitions', ['id' => $newDefinition->id]);
        $this->assertDatabaseHas('mm_specification_template_attributes', [
            'id' => $multiplexAssignment->id,
            'template_id' => $multiplex->id,
            'attribute_definition_id' => $multiplexDefinition->id,
        ]);

        $this->assertSame('Cat Updated', $cat->fresh()->name);
        $this->assertSame('MP-0001', $multiplex->fresh()->code);
    }

    public function test_active_units_are_selectable_and_input_type_unit_choices_persist_independently_of_master_data_type(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'CT-0001', 'Cat');
        $activeUnit = $this->createUnit('MM', 'Millimeter');
        $anotherUnit = $this->createUnit('CM', 'Centimeter');
        $inactiveUnit = $this->createUnit('M', 'Meter');
        $inactiveUnit->update(['is_active' => false]);
        $definition = $this->createDefinition($category, 'Ketebalan', 'number');

        $response = $this->actingAs($user)->get(route('mm05.templates.index', [
            'edit_template' => $template->id,
        ]));
        $response->assertOk()
            ->assertSee($activeUnit->name.' ('.$activeUnit->code.')')
            ->assertDontSee($inactiveUnit->name);

        $this->post(route('mm05.templates.attributes.attach', $template), [
            'attribute_definition_id' => $definition->id,
            'input_type' => 'text',
            'unit_id' => $activeUnit->id,
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $assignment = MmSpecificationTemplateAttribute::query()
            ->where('template_id', $template->id)
            ->where('attribute_definition_id', $definition->id)
            ->sole();

        $this->assertSame('number', $definition->fresh()->data_type);
        $this->assertSame('text', $assignment->input_type);
        $this->assertTrue($assignment->unit_id_is_explicit);
        $this->assertSame($activeUnit->id, $assignment->resolvedUnitId());

        $this->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $assignment->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'boolean',
                    'unit_id' => $anotherUnit->id,
                ],
            ],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $assignment->refresh();
        $this->assertSame('boolean', $assignment->input_type);
        $this->assertSame('number', $definition->fresh()->data_type);
        $this->assertSame($anotherUnit->id, $assignment->resolvedUnitId());

        $this->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $assignment->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'number',
                    'unit_id' => '',
                ],
            ],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $assignment->refresh();
        $this->assertSame('number', $assignment->input_type);
        $this->assertNull($assignment->resolvedUnitId());

        $reopened = $this->get(route('mm05.templates.index', ['edit_template' => $template->id]));
        $reopened->assertOk()->assertSee('value="" selected', false);
        $this->assertSame('number', $definition->fresh()->data_type);
    }

    public function test_template_unit_choices_allow_specification_only_units_and_preserve_legacy_assignments(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'CT-0001', 'Cat');
        $specificationOnlyUnit = $this->createUnit('MM', 'Millimeter');
        $specificationOnlyUnit->update([
            'usage_group' => null,
            'is_available_for_new_material' => false,
        ]);
        $nonSpecificationUnit = MmUnit::query()->create([
            'code' => 'NON-SPEC-'.Str::upper(Str::random(6)),
            'name' => 'Material Only Unit',
            'usage_group' => 'both',
            'is_specification_unit' => false,
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);
        $legacyUnit = MmUnit::query()->create([
            'code' => 'LEGACY-SPEC-'.Str::upper(Str::random(6)),
            'name' => 'Legacy Template Unit',
            'usage_group' => 'both',
            'is_specification_unit' => false,
            'is_available_for_new_material' => false,
            'is_active' => true,
        ]);
        $definition = $this->createDefinition($category, 'Ketebalan', 'number');
        $legacyAssignment = $this->attachDefinition($template, $definition, 0, $legacyUnit);

        $response = $this->actingAs($user)->get(route('mm05.templates.index', [
            'edit_template' => $template->id,
        ]));
        $response->assertOk()
            ->assertSee($specificationOnlyUnit->code.' — '.$specificationOnlyUnit->name)
            ->assertSee($legacyUnit->name.' (satuan lama; dipertahankan)')
            ->assertDontSee($nonSpecificationUnit->code.' — '.$nonSpecificationUnit->name);

        $this->post(route('mm05.templates.attributes.attach', $template), [
            'attribute_definition_id' => $this->createDefinition($category, 'Finishing', 'text')->id,
            'input_type' => 'text',
            'unit_id' => $nonSpecificationUnit->id,
        ])->assertSessionHasErrors('unit_id');

        $this->put(route('mm05.templates.update', $template), [
            'attributes' => [[
                'id' => $legacyAssignment->id,
                'is_required' => '0',
                'display_order' => 1,
                'input_type' => 'text',
                'unit_id' => $legacyUnit->id,
            ]],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $this->assertDatabaseHas('mm_specification_template_attributes', [
            'id' => $legacyAssignment->id,
            'unit_id' => $legacyUnit->id,
            'input_type' => 'text',
        ]);
    }

    public function test_text_template_attributes_can_use_change_and_clear_units(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'CT-0001', 'Cat');
        $millimeter = $this->createUnit('MM', 'Millimeter');
        $centimeter = $this->createUnit('CM', 'Centimeter');
        $definition = $this->createDefinition($category, 'Kode Warna', 'text');

        $this->actingAs($user)->post(route('mm05.templates.attributes.attach', $template), [
            'attribute_definition_id' => $definition->id,
            'input_type' => 'text',
            'unit_id' => $millimeter->id,
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $assignment = MmSpecificationTemplateAttribute::query()
            ->where('template_id', $template->id)
            ->where('attribute_definition_id', $definition->id)
            ->sole();
        $this->assertSame('text', $definition->fresh()->data_type);
        $this->assertSame($millimeter->id, $assignment->resolvedUnitId());

        $this->put(route('mm05.templates.attributes.update', [$template, $assignment]), [
            'is_required' => '1',
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));
        $assignment->refresh();
        $this->assertSame($millimeter->id, $assignment->resolvedUnitId());

        $attributesUrl = route('mm05.templates.attributes.index', [
            'template' => $template,
            'category_id' => $category->id,
        ]);
        $this->get($attributesUrl)
            ->assertOk()
            ->assertJsonPath('attributes.0.unit_id', $millimeter->id)
            ->assertJsonPath('attributes.0.unit', $millimeter->name);

        $editResponse = $this->get(route('mm05.templates.index', ['edit_template' => $template->id]));
        $table = $this->attributeTableContent($editResponse->getContent(), $template->id);
        $this->assertStringContainsString('name="attributes[0][unit_id]"', $table);
        $this->assertStringContainsString('value="'.$millimeter->id.'" selected', $table);

        $this->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $assignment->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'text',
                    'unit_id' => $centimeter->id,
                ],
            ],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $assignment->refresh();
        $this->assertSame($centimeter->id, $assignment->resolvedUnitId());
        $reopened = $this->get(route('mm05.templates.index', ['edit_template' => $template->id]));
        $table = $this->attributeTableContent($reopened->getContent(), $template->id);
        $this->assertStringContainsString('value="'.$centimeter->id.'" selected', $table);

        $this->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $assignment->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'text',
                    'unit_id' => '',
                ],
            ],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $assignment->refresh();
        $this->assertTrue($assignment->unit_id_is_explicit);
        $this->assertNull($assignment->resolvedUnitId());
        $this->assertNull($definition->fresh()->unit_id);

        $reopened = $this->get(route('mm05.templates.index', ['edit_template' => $template->id]));
        $table = $this->attributeTableContent($reopened->getContent(), $template->id);
        $this->assertStringContainsString('name="attributes[0][unit_id]"', $table);
        $this->assertStringContainsString('value="" selected', $table);
    }

    public function test_legacy_assignment_without_explicit_unit_column_resolves_existing_unit_values(): void
    {
        $category = $this->createCategory();
        $unit = $this->createUnit('MM', 'Millimeter');
        $definition = $this->createDefinition($category, 'Ketebalan', 'number', $unit);

        $assignedUnit = new MmSpecificationTemplateAttribute;
        $assignedUnit->setRawAttributes(['unit_id' => $unit->id]);
        $assignedUnit->setRelation('attributeDefinition', $this->createDefinition($category, 'Panjang', 'number'));

        $inheritedUnit = new MmSpecificationTemplateAttribute;
        $inheritedUnit->setRawAttributes(['unit_id' => null]);
        $inheritedUnit->setRelation('attributeDefinition', $definition);

        $this->assertTrue($assignedUnit->hasExplicitUnitPreference());
        $this->assertSame($unit->id, $assignedUnit->resolvedUnitId());
        $this->assertFalse($inheritedUnit->hasExplicitUnitPreference());
        $this->assertSame($unit->id, $inheritedUnit->resolvedUnitId());
    }

    public function test_reordered_template_rows_are_persisted_and_return_in_the_same_order(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'MP-0001', 'Multiplex');
        $definitions = collect(['Grade', 'Ketebalan', 'Panjang'])
            ->map(fn (string $label, int $index): MmAttributeDefinition => $this->createDefinition(
                $category,
                $label,
                $label === 'Grade' ? 'text' : 'number',
                null,
                $index,
            ));
        $assignments = $definitions->map(fn (MmAttributeDefinition $definition, int $index) => $this->attachDefinition(
            $template,
            $definition,
            $index + 1,
        ));

        $this->actingAs($user)->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $assignments[0]->id,
                    'is_required' => '0',
                    'display_order' => 3,
                    'input_type' => 'text',
                    'unit_id' => '',
                ],
                [
                    'id' => $assignments[1]->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'number',
                    'unit_id' => '',
                ],
                [
                    'id' => $assignments[2]->id,
                    'is_required' => '0',
                    'display_order' => 2,
                    'input_type' => 'number',
                    'unit_id' => '',
                ],
            ],
        ])->assertRedirect(route('mm05.templates.index', ['edit_template' => $template->id]));

        $this->assertSame(
            [$definitions[1]->id, $definitions[2]->id, $definitions[0]->id],
            $template->fresh()->templateAttributes()->pluck('attribute_definition_id')->all(),
        );

        $reopened = $this->get(route('mm05.templates.index', ['edit_template' => $template->id]));
        $table = $this->attributeTableContent($reopened->getContent(), $template->id);
        $this->assertLessThan(strpos($table, 'Panjang'), strpos($table, 'Ketebalan'));
        $this->assertLessThan(strpos($table, 'Grade'), strpos($table, 'Panjang'));
    }

    public function test_template_order_rejects_partial_or_noncontiguous_attribute_lists(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'CT-0001', 'Cat');
        $first = $this->attachDefinition($template, $this->createDefinition($category, 'Merek', 'text'), 1);
        $second = $this->attachDefinition($template, $this->createDefinition($category, 'Warna', 'text'), 2);

        $this->actingAs($user)
            ->put(route('mm05.templates.attributes.update', [$template, $first]), [
                'display_order' => 2,
            ])
            ->assertSessionHasErrors('display_order');

        $this->actingAs($user)->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $first->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'text',
                    'unit_id' => '',
                ],
            ],
        ])->assertSessionHasErrors('attributes');

        $this->put(route('mm05.templates.update', $template), [
            'attributes' => [
                [
                    'id' => $first->id,
                    'is_required' => '0',
                    'display_order' => 1,
                    'input_type' => 'text',
                    'unit_id' => '',
                ],
                [
                    'id' => $second->id,
                    'is_required' => '0',
                    'display_order' => 3,
                    'input_type' => 'text',
                    'unit_id' => '',
                ],
            ],
        ])->assertSessionHasErrors('attributes');

        $this->assertSame(1, $first->fresh()->display_order);
        $this->assertSame(2, $second->fresh()->display_order);
    }

    public function test_unused_template_can_be_deleted_without_deleting_master_definitions_but_used_template_is_preserved(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $unusedTemplate = $this->createTemplate($category, 'CT-0001', 'Unused Cat');
        $usedTemplate = $this->createTemplate($category, 'MP-0001', 'Used Multiplex');
        $definition = $this->createDefinition($category, 'Warna', 'text');
        $unusedAssignment = $this->attachDefinition($unusedTemplate, $definition, 1);
        $usedAssignment = $this->attachDefinition($usedTemplate, $definition, 1);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'specification_template_id' => $usedTemplate->id,
            'code' => 'MAT-'.Str::upper(Str::random(8)),
            'name' => 'Material with specification template',
        ]);

        $this->actingAs($user)->delete(route('mm05.templates.delete', $unusedTemplate))
            ->assertRedirect(route('mm05.templates.index'));

        $this->assertDatabaseMissing('mm_specification_templates', ['id' => $unusedTemplate->id]);
        $this->assertDatabaseMissing('mm_specification_template_attributes', ['id' => $unusedAssignment->id]);
        $this->assertDatabaseHas('mm_attribute_definitions', ['id' => $definition->id]);

        $this->delete(route('mm05.templates.delete', $usedTemplate))
            ->assertSessionHasErrors('template');

        $this->assertDatabaseHas('mm_specification_templates', ['id' => $usedTemplate->id]);
        $this->assertDatabaseHas('mm_specification_template_attributes', ['id' => $usedAssignment->id]);
        $this->assertDatabaseHas('mm_materials', [
            'id' => $material->id,
            'specification_template_id' => $usedTemplate->id,
        ]);
    }

    public function test_edit_material_input_name_uses_master_data_type_when_template_input_type_differs(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $template = $this->createTemplate($category, 'TPL-000123', 'Numeric UI for text data');
        $baseUnit = $this->createUnit('EA', 'Each');
        $definition = $this->createDefinition($category, 'Kode', 'text');
        $assignment = $this->attachDefinition($template, $definition, 1);
        $assignment->update(['input_type' => 'number']);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'specification_template_id' => $template->id,
            'code' => 'MAT-'.Str::upper(Str::random(8)),
            'name' => 'Material text specification',
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'variant_name' => $material->name,
            'base_unit_id' => $baseUnit->id,
        ]);
        MmVariantAttribute::query()->create([
            'variant_id' => $variant->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => '00123',
        ]);

        $response = $this->actingAs($user)->get(route('mm05.materials.show', $material));
        $response->assertOk()
            ->assertSee('type="number"', false)
            ->assertSee('name="attributes[0][value_text]"', false)
            ->assertSee('value="00123"', false)
            ->assertDontSee('name="attributes[0][value_number]"', false);
    }

    public function test_an_assignment_from_another_template_cannot_be_updated_or_removed(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = $this->createCategory();
        $cat = $this->createTemplate($category, 'CT-0001', 'Cat');
        $multiplex = $this->createTemplate($category, 'MP-0001', 'Multiplex');
        $definition = $this->createDefinition($category, 'Grade', 'text');
        $assignment = $this->attachDefinition($multiplex, $definition, 0);

        $this->actingAs($user)
            ->put(route('mm05.templates.attributes.update', [$cat, $assignment]), [
                'is_required' => '1',
            ])
            ->assertNotFound();

        $this->delete(route('mm05.templates.attributes.detach', [$cat, $assignment]))
            ->assertNotFound();

        $this->assertDatabaseHas('mm_specification_template_attributes', [
            'id' => $assignment->id,
            'template_id' => $multiplex->id,
        ]);
    }

    public function test_admin_bpm_cannot_manage_specification_templates(): void
    {
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $category = $this->createCategory();

        $this->actingAs($admin)->get(route('mm05.templates.index'))->assertForbidden();

        $this->post(route('mm05.templates.store'), [
            'name' => 'Unauthorized Template',
            'category_id' => $category->id,
        ])->assertForbidden();

        $template = $this->createTemplate($category, 'TPL-ADMIN-001', 'Admin cannot delete');
        $this->delete(route('mm05.templates.delete', $template))->assertForbidden();

        $this->assertDatabaseMissing('mm_specification_templates', ['name' => 'Unauthorized Template']);
        $this->assertDatabaseHas('mm_specification_templates', ['id' => $template->id]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $user = User::query()->create([
            'name' => $roleCode.' User',
            'email' => Str::uuid().'@mm-template.test',
            'password' => Hash::make(Str::random(20)),
        ]);
        $role = BpmRole::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => str_replace('_', ' ', $roleCode)],
        );
        $user->roles()->attach($role);

        return $user;
    }

    private function createCategory(): MmCategory
    {
        $suffix = Str::upper(Str::random(8));

        return MmCategory::query()->create([
            'code' => 'T'.$suffix,
            'name' => 'Template Test '.$suffix,
            'is_active' => true,
        ]);
    }

    private function createTemplate(MmCategory $category, string $code, string $name): MmSpecificationTemplate
    {
        return MmSpecificationTemplate::query()->create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createUnit(string $code, string $name): MmUnit
    {
        return MmUnit::query()->create([
            'code' => $code.'-'.Str::upper(Str::random(6)),
            'name' => $name,
            'dimension' => 'length',
            'usage_group' => 'both',
            'is_specification_unit' => true,
            'is_available_for_new_material' => true,
            'is_active' => true,
        ]);
    }

    private function createDefinition(
        MmCategory $category,
        string $label,
        string $dataType,
        ?MmUnit $unit = null,
        int $sortOrder = 0,
    ): MmAttributeDefinition {
        return MmAttributeDefinition::query()->create([
            'category_id' => $category->id,
            'code' => 'A'.Str::upper(Str::random(10)),
            'label' => $label,
            'data_type' => $dataType,
            'unit_id' => $unit?->id,
            'is_required' => false,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function attachDefinition(
        MmSpecificationTemplate $template,
        MmAttributeDefinition $definition,
        int $order,
        ?MmUnit $unit = null,
    ): MmSpecificationTemplateAttribute {
        return MmSpecificationTemplateAttribute::query()->create([
            'template_id' => $template->id,
            'attribute_definition_id' => $definition->id,
            'is_required' => false,
            'display_order' => $order,
            'input_type' => $definition->data_type,
            'unit_id' => $unit?->id,
            'unit_id_is_explicit' => $unit !== null,
        ]);
    }

    private function attributeTableContent(string $html, int $templateId): string
    {
        preg_match('/<dialog id="editTemplate'.$templateId.'".*?<\/dialog>/s', $html, $dialog);
        $this->assertArrayHasKey(0, $dialog);
        preg_match('/<table class="template-attribute-table">.*?<\/table>/s', $dialog[0], $table);
        $this->assertArrayHasKey(0, $table);

        return $table[0];
    }
}
