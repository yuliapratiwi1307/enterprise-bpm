<?php

namespace App\Services;

use App\Models\MmAttributeDefinition;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmVariantService
{
    public function createVariant(array $data, ?int $actorId = null): MmMaterialVariant
    {
        MmSafety::check();
        MmSafety::actor($actorId);
        $v = MmSafety::validate($data, $this->rules());
        MmSafety::active('mm_materials', (int) $v['material_id'], 'material_id');
        MmSafety::active('mm_units', (int) $v['base_unit_id'], 'base_unit_id');
        $v['created_by'] = $actorId;
        $v['updated_by'] = $actorId;

        return MmMaterialVariant::create($v);
    }

    public function updateVariant(MmMaterialVariant $variant, array $data, ?int $actorId = null): MmMaterialVariant
    {
        MmSafety::check($variant, 'mm_material_variants');
        MmSafety::actor($actorId);
        $variantId = (int) $variant->getKey();

        return DB::transaction(function () use ($variantId, $data, $actorId): MmMaterialVariant {
            $lockedVariant = MmMaterialVariant::query()
                ->whereKey($variantId)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedVariant, 'mm_material_variants');
            $v = MmSafety::validate($data, $this->rules($lockedVariant));

            if (isset($v['material_id']) && (int) $v['material_id'] !== (int) $lockedVariant->material_id) {
                MmSafety::active('mm_materials', (int) $v['material_id'], 'material_id');

                if ($lockedVariant->attributes()->exists()) {
                    throw ValidationException::withMessages([
                        'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
                    ]);
                }

                if ($lockedVariant->supplierQuotes()->exists()) {
                    throw ValidationException::withMessages([
                        'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki histori penawaran supplier.',
                    ]);
                }

                if ($lockedVariant->prices()->exists()) {
                    throw ValidationException::withMessages([
                        'material_id' => 'Material induk tidak dapat diubah karena varian sudah memiliki histori harga.',
                    ]);
                }
            }

            if (isset($v['base_unit_id']) && (int) $v['base_unit_id'] !== (int) $lockedVariant->base_unit_id) {
                MmSafety::active('mm_units', (int) $v['base_unit_id'], 'base_unit_id');
            }
            if ($actorId !== null) {
                $v['updated_by'] = $actorId;
            }
            $lockedVariant->update($v);

            return $lockedVariant->refresh();
        });
    }

    public function deactivateVariant(MmMaterialVariant $variant, ?int $actorId = null): MmMaterialVariant
    {
        MmSafety::check($variant, 'mm_material_variants');
        MmSafety::actor($actorId);
        $v = ['is_active' => false];
        if ($actorId !== null) {
            $v['updated_by'] = $actorId;
        }
        $variant->update($v);

        return $variant->refresh();
    }

    public function setAttributes(MmMaterialVariant $variant, array $attributes): void
    {
        MmSafety::check($variant, 'mm_material_variants');
        $variantId = (int) $variant->getKey();

        DB::transaction(function () use ($variantId, $attributes): void {
            $lockedVariant = MmMaterialVariant::query()
                ->whereKey($variantId)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedVariant, 'mm_material_variants');
            if (! $lockedVariant->is_active) {
                throw ValidationException::withMessages(['variant' => 'Varian tidak aktif.']);
            }
            // Lock in variant -> material -> definition order; category writers lock only their target row.
            $material = MmMaterial::query()
                ->whereKey($lockedVariant->material_id)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::active('mm_materials', (int) $lockedVariant->material_id, 'material_id');
            $materialCategory = (int) $material->category_id;
            $seen = [];
            foreach ($attributes as $index => $item) {
                if (! is_array($item)) {
                    throw ValidationException::withMessages(['attributes' => 'Setiap atribut harus berupa objek data yang valid.']);
                }

                $v = MmSafety::validate($item, [
                    'attribute_definition_id' => ['required', 'integer', Rule::exists('mm_attribute_definitions', 'id')],
                    'value_text' => ['nullable', 'string', 'max:500'],
                    'value_number' => ['nullable', 'numeric', 'between:-999999999999.999999,999999999999.999999'],
                    'value_boolean' => ['nullable', 'boolean'],
                ]);
                $id = (int) $v['attribute_definition_id'];
                if (isset($seen[$id])) {
                    throw ValidationException::withMessages(['attributes' => 'Definisi atribut duplikat.']);
                }
                $seen[$id] = true;
                $definition = MmAttributeDefinition::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (! $definition->is_active || (int) $definition->category_id !== (int) $materialCategory) {
                    throw ValidationException::withMessages(['attributes' => 'Definisi atribut harus aktif dan sesuai kategori.']);
                }
                $key = ['text' => 'value_text', 'number' => 'value_number', 'boolean' => 'value_boolean'][$definition->data_type] ?? null;
                if ($key === null) {
                    throw ValidationException::withMessages(['attributes' => 'Tipe atribut tidak dikenal.']);
                }
                $value = $v[$key] ?? null;
                if ($definition->is_required && $value === null) {
                    throw ValidationException::withMessages(['attributes' => 'Atribut wajib belum diisi.']);
                }
                foreach (['value_text', 'value_number', 'value_boolean'] as $column) {
                    if ($column !== $key && array_key_exists($column, $v) && $v[$column] !== null) {
                        throw ValidationException::withMessages(['attributes' => 'Tipe nilai tidak sesuai definisi.']);
                    }
                }
                $lockedVariant->attributes()->updateOrCreate(
                    ['attribute_definition_id' => $id],
                    ['value_text' => $key === 'value_text' ? $value : null,
                        'value_number' => $key === 'value_number' ? $value : null,
                        'value_boolean' => $key === 'value_boolean' ? $value : null]
                );
            }

            // The submitted set must contain every active required definition for
            // the material category. Checking only submitted rows would allow a
            // required attribute to be silently omitted.
            $requiredIds = MmAttributeDefinition::query()
                ->where('category_id', $materialCategory)
                ->where('is_active', true)
                ->where('is_required', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missing = array_values(array_diff($requiredIds, array_keys($seen)));
            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'attributes' => 'Ada atribut wajib yang belum dikirim untuk kategori material ini.',
                ]);
            }
        });
    }

    private function rules(?MmMaterialVariant $variant = null): array
    {
        $p = $variant ? ['sometimes'] : [];
        $unique = Rule::unique('mm_material_variants', 'sku');
        if ($variant) {
            $unique->ignore($variant->id);
        }

        return [
            'material_id' => [...$p, 'required', 'integer', Rule::exists('mm_materials', 'id')],
            'sku' => [...$p, 'required', 'string', 'max:90', $unique],
            'variant_name' => [...$p, 'required', 'string', 'max:255'],
            'base_unit_id' => [...$p, 'required', 'integer', Rule::exists('mm_units', 'id')],
            'specification_note' => [...$p, 'nullable', 'string'],
        ];
    }
}
