<?php

namespace App\Services;

use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmMasterDataService
{
    private const DIMENSIONS = ['count', 'length', 'area', 'volume', 'mass'];

    private const CATEGORY_HIERARCHY_LOCK_NAMESPACE = 1296906293;

    private const CATEGORY_HIERARCHY_LOCK_KEY = 5;

    public function createUnit(array $data): MmUnit
    {
        MmSafety::check();

        $v = MmSafety::validate($data, [
            'name' => ['required', 'string', 'max:100'],
            'dimension' => ['nullable', Rule::in(self::DIMENSIONS)],
            'unit_usage' => ['sometimes', 'required', Rule::in(['specification', 'price', 'both'])],
        ]);

        $usage = $v['unit_usage'] ?? 'both';
        unset($v['unit_usage']);
        $v['usage_group'] = $usage === 'specification' ? null : ($usage === 'price' ? 'price' : 'both');
        $v['is_specification_unit'] = $usage !== 'price';

        return DB::transaction(function () use ($v): MmUnit {
            // PostgreSQL transaction-level advisory lock for unique UOM sequence.
            DB::select('SELECT pg_advisory_xact_lock(1296906293, 6)');

            $codes = MmUnit::query()->pluck('code');
            $max = 0;

            foreach ($codes as $code) {
                if (preg_match('/^UOM-(\\d+)$/', $code, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            }

            $v['code'] = 'UOM-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);

            return MmUnit::create($v);
        });
    }

    public function updateUnit(MmUnit $unit, array $data): MmUnit
    {
        MmSafety::check($unit, 'mm_units');

        $v = MmSafety::validate($data, [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'dimension' => ['sometimes', 'nullable', Rule::in(self::DIMENSIONS)],
            'unit_usage' => ['sometimes', 'required', Rule::in(['specification', 'price', 'both'])],
        ]);

        if (array_key_exists('unit_usage', $v)) {
            $usage = $v['unit_usage'];
            unset($v['unit_usage']);
            $v['usage_group'] = $usage === 'specification' ? null : ($usage === 'price' ? 'price' : 'both');
            $v['is_specification_unit'] = $usage !== 'price';
        }

        $unit->update($v);

        return $unit->refresh();
    }

    public function deactivateUnit(MmUnit $unit): MmUnit
    {
        MmSafety::check($unit, 'mm_units');
        $unit->update(['is_active' => false]);

        return $unit->refresh();
    }

    public function createCategory(array $data): MmCategory
    {
        MmSafety::check();

        return DB::transaction(function () use ($data): MmCategory {
            $this->lockCategoryHierarchy();
            $v = MmSafety::validate($data, [
                'code' => ['required', 'string', 'max:40', Rule::unique('mm_categories', 'code')],
                'name' => ['required', 'string', 'max:150'],
                'parent_id' => ['nullable', 'integer', Rule::exists('mm_categories', 'id')],
            ]);
            if (! empty($v['parent_id'])) {
                MmSafety::active('mm_categories', (int) $v['parent_id'], 'parent_id');
            }

            return MmCategory::create($v);
        });
    }

    public function updateCategory(int $categoryId, array $data): MmCategory
    {
        MmSafety::check();

        return DB::transaction(function () use ($categoryId, $data): MmCategory {
            $this->lockCategoryHierarchy();
            $category = MmCategory::query()
                ->whereKey($categoryId)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($category, 'mm_categories');
            $v = MmSafety::validate($data, [
                'code' => ['sometimes', 'required', 'string', 'max:40', Rule::unique('mm_categories', 'code')->ignore($category->id)],
                'name' => ['sometimes', 'required', 'string', 'max:150'],
                'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('mm_categories', 'id')],
            ]);
            if (array_key_exists('parent_id', $v) && $v['parent_id'] !== null) {
                MmSafety::active('mm_categories', (int) $v['parent_id'], 'parent_id');
                $seen = [(int) $category->id => true];
                $cursor = (int) $v['parent_id'];
                while ($cursor) {
                    if (isset($seen[$cursor])) {
                        throw ValidationException::withMessages(['parent_id' => 'Hierarki kategori membentuk siklus.']);
                    }
                    $seen[$cursor] = true;
                    $cursor = (int) (DB::table('mm_categories')->where('id', $cursor)->value('parent_id') ?? 0);
                }
            }
            $category->update($v);

            return $category->refresh();
        });
    }

    public function deactivateCategory(MmCategory $category): MmCategory
    {
        MmSafety::check($category, 'mm_categories');
        $category->update(['is_active' => false]);

        return $category->refresh();
    }

    public function createMaterial(array $data, ?int $actorId = null): MmMaterial
    {
        MmSafety::check();
        MmSafety::actor($actorId);
        $v = MmSafety::validate($data, [
            'category_id' => ['required', 'integer', Rule::exists('mm_categories', 'id')],
            'code' => ['required', 'string', 'max:60', Rule::unique('mm_materials', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
        MmSafety::active('mm_categories', (int) $v['category_id'], 'category_id');
        $v['created_by'] = $actorId;
        $v['updated_by'] = $actorId;

        return MmMaterial::create($v);
    }

    public function updateMaterial(MmMaterial $material, array $data, ?int $actorId = null): MmMaterial
    {
        MmSafety::check($material, 'mm_materials');
        MmSafety::actor($actorId);
        $materialId = (int) $material->getKey();

        return DB::transaction(function () use ($materialId, $data, $actorId): MmMaterial {
            $lockedMaterial = MmMaterial::query()
                ->whereKey($materialId)
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedMaterial, 'mm_materials');
            $v = MmSafety::validate($data, [
                'category_id' => ['sometimes', 'required', 'integer', Rule::exists('mm_categories', 'id')],
                'code' => ['sometimes', 'required', 'string', 'max:60', Rule::unique('mm_materials', 'code')->ignore($lockedMaterial->id)],
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string'],
            ]);
            if (array_key_exists('category_id', $v) && (int) $v['category_id'] !== (int) $lockedMaterial->category_id) {
                MmSafety::active('mm_categories', (int) $v['category_id'], 'category_id');
                if ($lockedMaterial->variants()->whereHas('attributes')->exists()) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori material tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.',
                    ]);
                }
            }
            if ($actorId !== null) {
                $v['updated_by'] = $actorId;
            }
            $lockedMaterial->update($v);

            return $lockedMaterial->refresh();
        });
    }

    public function activateMaterial(MmMaterial $material, ?int $actorId = null): MmMaterial
    {
        MmSafety::check($material, 'mm_materials');
        MmSafety::actor($actorId);
        $v = ['is_active' => true];
        if ($actorId !== null) {
            $v['updated_by'] = $actorId;
        }
        $material->update($v);

        return $material->refresh();
    }

    public function deactivateMaterial(MmMaterial $material, ?int $actorId = null): MmMaterial
    {
        MmSafety::check($material, 'mm_materials');
        MmSafety::actor($actorId);
        $v = ['is_active' => false];
        if ($actorId !== null) {
            $v['updated_by'] = $actorId;
        }
        $material->update($v);

        return $material->refresh();
    }

    public function createSupplier(array $data): MmSupplier
    {
        MmSafety::check();
        $v = MmSafety::validate($data, $this->supplierRules());

        return DB::transaction(function () use ($v): MmSupplier {
            // Serialize allocation even when there are no supplier rows to lock individually.
            DB::statement('LOCK TABLE mm_suppliers IN EXCLUSIVE MODE');

            return MmSupplier::create([
                ...$v,
                'code' => $this->nextSupplierCode(),
            ]);
        });
    }

    public function updateSupplier(MmSupplier $supplier, array $data): MmSupplier
    {
        MmSafety::check($supplier, 'mm_suppliers');
        $v = MmSafety::validate($data, $this->supplierRules($supplier));
        $supplier->update($v);

        return $supplier->refresh();
    }

    public function deactivateSupplier(MmSupplier $supplier): MmSupplier
    {
        MmSafety::check($supplier, 'mm_suppliers');
        $supplier->update(['is_active' => false]);

        return $supplier->refresh();
    }

    public function activateSupplier(MmSupplier $supplier): MmSupplier
    {
        MmSafety::check($supplier, 'mm_suppliers');
        $supplier->update(['is_active' => true]);

        return $supplier->refresh();
    }

    private function nextSupplierCode(): string
    {
        $highestSequence = '0';

        foreach (MmSupplier::query()->pluck('code') as $code) {
            if (preg_match('/\ASUP-(\d+)\z/', $code, $matches) !== 1) {
                continue;
            }

            $sequence = ltrim($matches[1], '0') ?: '0';
            if (
                strlen($sequence) > strlen($highestSequence)
                || (strlen($sequence) === strlen($highestSequence) && strcmp($sequence, $highestSequence) > 0)
            ) {
                $highestSequence = $sequence;
            }
        }

        $digits = str_split($highestSequence);
        for ($index = count($digits) - 1; $index >= 0; $index--) {
            if ($digits[$index] !== '9') {
                $digits[$index] = (string) ((int) $digits[$index] + 1);
                break;
            }

            $digits[$index] = '0';
            if ($index === 0) {
                array_unshift($digits, '1');
            }
        }

        return 'SUP-'.str_pad(implode('', $digits), 3, '0', STR_PAD_LEFT);
    }

    private function supplierRules(?MmSupplier $supplier = null): array
    {
        $prefix = $supplier ? ['sometimes'] : [];

        return [
            'name' => [...$prefix, 'required', 'string', 'max:255'],
            'contact_name' => [...$prefix, 'nullable', 'string', 'max:150'],
            'phone' => [...$prefix, 'nullable', 'string', 'max:60'],
            'email' => [...$prefix, 'nullable', 'email', 'max:255'],
            'notes' => [...$prefix, 'nullable', 'string'],
        ];
    }

    private function lockCategoryHierarchy(): void
    {
        // All application mutations of category parent_id must share this transaction lock.
        DB::select('SELECT pg_advisory_xact_lock(?, ?)', [
            self::CATEGORY_HIERARCHY_LOCK_NAMESPACE,
            self::CATEGORY_HIERARCHY_LOCK_KEY,
        ]);
    }
}
