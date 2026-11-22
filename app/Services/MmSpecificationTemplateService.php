<?php

namespace App\Services;

use App\Models\MmAttributeDefinition;
use App\Models\MmSpecificationTemplate;
use App\Models\MmSpecificationTemplateAttribute;
use App\Models\MmUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmSpecificationTemplateService
{
    public function createTemplate(array $data): MmSpecificationTemplate
    {
        MmSafety::check();

        return DB::transaction(function () use ($data): MmSpecificationTemplate {
            $v = MmSafety::validate($data, [
                'category_id' => ['required', 'integer', Rule::exists('mm_categories', 'id')],
                'code' => ['prohibited'],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string'],
            ]);

            MmSafety::active('mm_categories', (int) $v['category_id'], 'category_id');
            DB::selectOne('SELECT pg_advisory_xact_lock(hashtext(?), ?)', ['mm05_specification_template_code', 1]);

            return MmSpecificationTemplate::query()->create([
                'category_id' => $v['category_id'],
                'code' => $this->nextTemplateCode(),
                'name' => trim($v['name']),
                'description' => $v['description'] ?? null,
                'is_active' => true,
            ]);
        });
    }

    public function updateTemplate(MmSpecificationTemplate $template, array $data): MmSpecificationTemplate
    {
        MmSafety::check($template, 'mm_specification_templates');

        return DB::transaction(function () use ($template, $data): MmSpecificationTemplate {
            $lockedTemplate = MmSpecificationTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();
            MmSafety::check($lockedTemplate, 'mm_specification_templates');
            $v = MmSafety::validate($data, [
                'category_id' => ['sometimes', 'required', 'integer', Rule::exists('mm_categories', 'id')],
                'code' => ['prohibited'],
                'name' => ['sometimes', 'required', 'string', 'max:150'],
                'description' => ['sometimes', 'nullable', 'string'],
                'attributes' => ['sometimes', 'array'],
                'attributes.*' => ['required', 'array:id,is_required,display_order,input_type,unit_id'],
                'attributes.*.id' => ['required', 'integer', 'distinct'],
                'attributes.*.is_required' => ['required', 'boolean'],
                'attributes.*.display_order' => ['required', 'integer', 'distinct', 'min:1', 'max:65535'],
                'attributes.*.input_type' => ['required', 'string', Rule::in(['text', 'number', 'boolean'])],
                'attributes.*.unit_id' => ['nullable', 'integer', Rule::exists('mm_units', 'id')],
            ]);

            if (isset($v['category_id'])) {
                MmSafety::active('mm_categories', (int) $v['category_id'], 'category_id');
            }

            $categoryId = (int) ($v['category_id'] ?? $lockedTemplate->category_id);
            if ($categoryId !== (int) $lockedTemplate->category_id) {
                if ($lockedTemplate->materials()->exists()) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori tidak dapat diubah karena template sudah digunakan oleh material.',
                    ]);
                }

                if ($lockedTemplate->templateAttributes()->whereHas(
                    'attributeDefinition',
                    fn ($query) => $query->where('category_id', '!=', $categoryId),
                )->exists()) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori tidak dapat diubah karena ada atribut template yang berasal dari kategori lain.',
                    ]);
                }
            }

            $attributeUpdates = $v['attributes'] ?? [];
            if (array_key_exists('attributes', $v)) {
                $existingIds = $lockedTemplate->templateAttributes()
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();
                $submittedIds = collect($attributeUpdates)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                if ($existingIds !== $submittedIds) {
                    throw ValidationException::withMessages([
                        'attributes' => 'Data pengurutan harus mencakup seluruh atribut pada template.',
                    ]);
                }

                $submittedPositions = collect($attributeUpdates)
                    ->pluck('display_order')
                    ->map(fn ($position): int => (int) $position)
                    ->sort()
                    ->values()
                    ->all();
                $expectedPositions = $existingIds === [] ? [] : range(1, count($existingIds));

                if ($submittedPositions !== $expectedPositions) {
                    throw ValidationException::withMessages([
                        'attributes' => 'Urutan atribut harus berurutan mulai dari 1.',
                    ]);
                }
            }

            $templateUpdates = array_intersect_key($v, array_flip(['category_id', 'name', 'description']));
            if ($templateUpdates !== []) {
                $lockedTemplate->update($templateUpdates);
            }

            foreach ($attributeUpdates as $attributeData) {
                $assignment = $lockedTemplate->templateAttributes()
                    ->whereKey($attributeData['id'])
                    ->first();

                if ($assignment === null) {
                    throw ValidationException::withMessages([
                        'attributes' => 'Salah satu atribut tidak termasuk dalam template yang dipilih.',
                    ]);
                }

                $configuration = [
                    'is_required' => $attributeData['is_required'],
                    'display_order' => $attributeData['display_order'],
                    'input_type' => $attributeData['input_type'],
                ];
                if (array_key_exists('unit_id', $attributeData)) {
                    $configuration['unit_id'] = $attributeData['unit_id'];
                }

                $this->updateAttribute($lockedTemplate, $assignment, $configuration);
            }

            return $lockedTemplate->refresh();
        });
    }

    public function deleteTemplate(MmSpecificationTemplate $template): void
    {
        MmSafety::check($template, 'mm_specification_templates');

        DB::transaction(function () use ($template): void {
            $lockedTemplate = MmSpecificationTemplate::query()
                ->whereKey($template->id)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedTemplate, 'mm_specification_templates');

            if ($lockedTemplate->materials()->exists()) {
                throw ValidationException::withMessages([
                    'template' => 'Template tidak dapat dihapus karena sudah digunakan oleh material.',
                ]);
            }

            $lockedTemplate->templateAttributes()->delete();
            $lockedTemplate->deleteOrFail();
        });
    }

    public function activateTemplate(MmSpecificationTemplate $template): MmSpecificationTemplate
    {
        MmSafety::check($template, 'mm_specification_templates');
        $template->update(['is_active' => true]);

        return $template->refresh();
    }

    public function deactivateTemplate(MmSpecificationTemplate $template): MmSpecificationTemplate
    {
        MmSafety::check($template, 'mm_specification_templates');
        $template->update(['is_active' => false]);

        return $template->refresh();
    }

    public function attachAttribute(MmSpecificationTemplate $template, array $data): MmSpecificationTemplateAttribute
    {
        MmSafety::check($template, 'mm_specification_templates');

        return DB::transaction(function () use ($template, $data): MmSpecificationTemplateAttribute {
            $lockedTemplate = MmSpecificationTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();
            MmSafety::check($lockedTemplate, 'mm_specification_templates');
            $v = MmSafety::validate($data, [
                'attribute_definition_id' => ['required', 'integer', Rule::exists('mm_attribute_definitions', 'id')],
                'is_required' => ['sometimes', 'boolean'],
                'display_order' => ['prohibited'],
                'input_type' => ['sometimes', 'string', Rule::in(['text', 'number', 'boolean'])],
                'unit_id' => ['nullable', 'integer', Rule::exists('mm_units', 'id')],
                'option_values' => ['nullable', 'array'],
            ]);

            $definition = MmAttributeDefinition::query()->whereKey($v['attribute_definition_id'])->firstOrFail();
            if ((int) $definition->category_id !== (int) $lockedTemplate->category_id) {
                throw ValidationException::withMessages([
                    'attribute_definition_id' => 'Atribut harus sesuai kategori template.',
                ]);
            }
            if (! $definition->is_active) {
                throw ValidationException::withMessages([
                    'attribute_definition_id' => 'Atribut harus aktif sebelum dapat dipasang ke template.',
                ]);
            }

            $inputType = $v['input_type'] ?? $definition->data_type;
            $unitWasSpecified = array_key_exists('unit_id', $v);
            $unitId = $unitWasSpecified ? ($v['unit_id'] ?? null) : $definition->unit_id;
            $unitId = $this->validateAttributeConfiguration($unitId);
            $hasExplicitUnitFlag = $this->hasExplicitUnitFlag();
            $this->ensureCanClearInheritedUnit($definition, $unitId, $unitWasSpecified, $hasExplicitUnitFlag);

            $assignmentDefaults = [
                'is_required' => $v['is_required'] ?? false,
                'display_order' => $v['display_order'] ?? (int) $lockedTemplate->templateAttributes()->max('display_order') + 1,
                'input_type' => $inputType,
                'unit_id' => $unitWasSpecified ? $unitId : null,
                'option_values' => $v['option_values'] ?? null,
            ];
            if ($hasExplicitUnitFlag) {
                $assignmentDefaults['unit_id_is_explicit'] = $unitWasSpecified;
            }

            $assignment = $lockedTemplate->templateAttributes()->firstOrCreate(
                ['attribute_definition_id' => $definition->id],
                $assignmentDefaults,
            );

            $updates = [
                'is_required' => $v['is_required'] ?? $assignment->is_required,
                'display_order' => $v['display_order'] ?? $assignment->display_order,
                'input_type' => $inputType,
                'option_values' => $v['option_values'] ?? $assignment->option_values,
            ];
            if ($unitWasSpecified) {
                $updates['unit_id'] = $unitId;
                if ($hasExplicitUnitFlag) {
                    $updates['unit_id_is_explicit'] = true;
                }
            }
            $assignment->update($updates);

            return $assignment->refresh();
        });
    }

    public function updateAttribute(
        MmSpecificationTemplate $template,
        MmSpecificationTemplateAttribute $assignment,
        array $data,
    ): MmSpecificationTemplateAttribute {
        abort_unless((int) $assignment->template_id === (int) $template->id, 404);
        MmSafety::check($assignment, 'mm_specification_template_attributes');

        return DB::transaction(function () use ($template, $assignment, $data): MmSpecificationTemplateAttribute {
            $lockedAssignment = MmSpecificationTemplateAttribute::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedAssignment->template_id === (int) $template->id, 404);
            MmSafety::check($lockedAssignment, 'mm_specification_template_attributes');
            $v = MmSafety::validate($data, [
                'is_required' => ['sometimes', 'boolean'],
                'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
                'input_type' => ['sometimes', 'string', Rule::in(['text', 'number', 'boolean'])],
                'unit_id' => ['nullable', 'integer', Rule::exists('mm_units', 'id')],
                'option_values' => ['nullable', 'array'],
            ]);

            $definition = $lockedAssignment->attributeDefinition()->firstOrFail();
            if (! $definition->is_active) {
                throw ValidationException::withMessages([
                    'attribute' => 'Definisi atribut harus aktif untuk mengubah konfigurasi template.',
                ]);
            }

            $inputType = $v['input_type'] ?? $lockedAssignment->input_type;
            $unitWasSpecified = array_key_exists('unit_id', $v);
            $unitId = $unitWasSpecified
                ? ($v['unit_id'] ?? null)
                : $lockedAssignment->resolvedUnitId();
            $unitId = $this->validateAttributeConfiguration($unitId, $lockedAssignment->resolvedUnitId());
            $hasExplicitUnitFlag = $this->hasExplicitUnitFlag();
            $this->ensureCanClearInheritedUnit($definition, $unitId, $unitWasSpecified, $hasExplicitUnitFlag);

            $updates = [
                'is_required' => $v['is_required'] ?? $lockedAssignment->is_required,
                'display_order' => $v['display_order'] ?? $lockedAssignment->display_order,
                'input_type' => $inputType,
                'option_values' => $v['option_values'] ?? $lockedAssignment->option_values,
            ];
            if ($unitWasSpecified) {
                $updates['unit_id'] = $unitId;
                if ($hasExplicitUnitFlag) {
                    $updates['unit_id_is_explicit'] = true;
                }
            }
            $lockedAssignment->update($updates);

            return $lockedAssignment->refresh();
        });
    }

    private function validateAttributeConfiguration(mixed $unitId, ?int $existingUnitId = null): ?int
    {
        if ($unitId !== null) {
            $unit = MmUnit::query()->find((int) $unitId);
            if ($unit === null) {
                throw ValidationException::withMessages([
                    'unit_id' => 'Satuan yang dipilih tidak ditemukan.',
                ]);
            }

            $isRetainedExistingUnit = $existingUnitId !== null && (int) $existingUnitId === (int) $unit->id;
            if (! $isRetainedExistingUnit) {
                MmSafety::active('mm_units', (int) $unitId, 'unit_id');
                if (! $unit->supportsSpecification()) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Pilih satuan aktif yang tersedia untuk Template Spesifikasi.',
                    ]);
                }
            }
        }

        return $unitId === null ? null : (int) $unitId;
    }

    private function hasExplicitUnitFlag(): bool
    {
        return Schema::hasColumn('mm_specification_template_attributes', 'unit_id_is_explicit');
    }

    private function ensureCanClearInheritedUnit(
        MmAttributeDefinition $definition,
        ?int $unitId,
        bool $unitWasSpecified,
        bool $hasExplicitUnitFlag,
    ): void {
        if (! $hasExplicitUnitFlag && $unitWasSpecified && $unitId === null && $definition->unit_id !== null) {
            throw ValidationException::withMessages([
                'unit_id' => 'Database belum mendukung pilihan Tanpa Satuan untuk definisi yang memiliki satuan master. Migration MM-05 perlu diterapkan terlebih dahulu.',
            ]);
        }
    }

    public function detachAttribute(
        MmSpecificationTemplate $template,
        MmSpecificationTemplateAttribute $assignment,
    ): void {
        abort_unless((int) $assignment->template_id === (int) $template->id, 404);
        MmSafety::check($assignment, 'mm_specification_template_attributes');
        $assignment->delete();
    }

    private function nextTemplateCode(): string
    {
        $highestSequence = 0;

        foreach (MmSpecificationTemplate::query()->where('code', 'like', 'TPL-%')->pluck('code') as $code) {
            if (preg_match('/\ATPL-(\d+)\z/', $code, $matches) === 1) {
                $highestSequence = max($highestSequence, (int) $matches[1]);
            }
        }

        do {
            $code = 'TPL-'.str_pad((string) ++$highestSequence, 6, '0', STR_PAD_LEFT);
        } while (MmSpecificationTemplate::query()->where('code', $code)->exists());

        return $code;
    }
}
