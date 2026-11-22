<?php

namespace App\Services;

use App\Models\MmAttributeDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmAttributeDefinitionService
{
    private const DATA_TYPES = ['text', 'number', 'boolean'];

    public function createDefinition(array $data): MmAttributeDefinition
    {
        MmSafety::check();

        $v = MmSafety::validate($data, $this->rules(null, isset($data['category_id']) ? (int) $data['category_id'] : null));
        MmSafety::active('mm_categories', (int) $v['category_id'], 'category_id');
        $this->validateUnit($v['data_type'], $v['unit_id'] ?? null);

        $v['unit_id'] = $v['unit_id'] ?? null;
        $v['is_required'] = $v['is_required'] ?? false;
        $v['sort_order'] = $v['sort_order'] ?? 0;
        $v['is_active'] = true;

        return MmAttributeDefinition::create($v);
    }

    public function updateDefinition(MmAttributeDefinition $definition, array $data): MmAttributeDefinition
    {
        MmSafety::check($definition, 'mm_attribute_definitions');
        $definitionId = (int) $definition->getKey();

        return DB::transaction(function () use ($definitionId, $data): MmAttributeDefinition {
            $lockedDefinition = MmAttributeDefinition::query()
                ->whereKey($definitionId)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedDefinition, 'mm_attribute_definitions');
            $categoryId = isset($data['category_id'])
                ? (int) $data['category_id']
                : (int) $lockedDefinition->category_id;

            $v = MmSafety::validate($data, $this->rules($lockedDefinition, $categoryId));

            if (isset($v['category_id']) && (int) $v['category_id'] !== (int) $lockedDefinition->category_id) {
                MmSafety::active('mm_categories', (int) $v['category_id'], 'category_id');
                if ($lockedDefinition->variantAttributes()->exists()) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori tidak dapat diubah karena atribut ini sudah dipakai oleh varian material.',
                    ]);
                }
            }

            $nextType = $v['data_type'] ?? $lockedDefinition->data_type;
            $nextUnit = array_key_exists('unit_id', $v) ? $v['unit_id'] : $lockedDefinition->unit_id;

            if ($nextType !== $lockedDefinition->data_type && $lockedDefinition->variantAttributes()->exists()) {
                throw ValidationException::withMessages([
                    'data_type' => 'Tipe data tidak dapat diubah karena atribut ini sudah memiliki nilai pada varian material.',
                ]);
            }

            // Text and boolean attributes cannot carry a measurement unit.
            if ($nextType !== 'number' && ! empty($nextUnit)) {
                if (! array_key_exists('unit_id', $v) && array_key_exists('data_type', $v)) {
                    $v['unit_id'] = null;
                    $nextUnit = null;
                } else {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Satuan hanya dapat digunakan untuk atribut bertipe number.',
                    ]);
                }
            }

            if ($nextUnit !== null && (int) $nextUnit !== (int) $lockedDefinition->unit_id) {
                $this->validateUnit($nextType, $nextUnit);
            } elseif ($nextType !== 'number' && $nextUnit !== null) {
                $this->validateUnit($nextType, $nextUnit);
            }
            $lockedDefinition->update($v);

            return $lockedDefinition->refresh();
        });
    }

    public function deactivateDefinition(MmAttributeDefinition $definition): MmAttributeDefinition
    {
        MmSafety::check($definition, 'mm_attribute_definitions');
        $definition->update(['is_active' => false]);

        return $definition->refresh();
    }

    private function rules(?MmAttributeDefinition $definition, ?int $categoryId): array
    {
        $prefix = $definition ? ['sometimes'] : [];
        $unique = Rule::unique('mm_attribute_definitions', 'code')
            ->where(fn ($query) => $query->where('category_id', $categoryId));

        if ($definition) {
            $unique->ignore($definition->id);
        }

        return [
            'category_id' => [...$prefix, 'required', 'integer', Rule::exists('mm_categories', 'id')],
            'code' => [...$prefix, 'required', 'string', 'max:60', $unique],
            'label' => [...$prefix, 'required', 'string', 'max:150'],
            'data_type' => [...$prefix, 'required', 'string', Rule::in(self::DATA_TYPES)],
            'unit_id' => [...$prefix, 'nullable', 'integer', Rule::exists('mm_units', 'id')],
            'is_required' => [...$prefix, 'sometimes', 'boolean'],
            'sort_order' => [...$prefix, 'sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    private function validateUnit(string $dataType, mixed $unitId): void
    {
        if ($dataType !== 'number' && $unitId !== null) {
            throw ValidationException::withMessages([
                'unit_id' => 'Satuan hanya dapat digunakan untuk atribut bertipe number.',
            ]);
        }

        if ($unitId !== null) {
            MmSafety::active('mm_units', (int) $unitId, 'unit_id');
        }
    }
}
