<?php

namespace App\Http\Controllers;

use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmUnit;
use App\Services\MmAttributeDefinitionService;
use App\Services\MmAuthorization;
use App\Services\MmReferencePriceSelector;
use App\Services\MmSafety;
use App\Services\MmVariantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MmSpecificationController extends Controller
{
    public function definitions(Request $request): View
    {
        MmAuthorization::authorizeView($request);
        MmSafety::check();

        $definitions = MmAttributeDefinition::query()
            ->with(['category', 'unit'])
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(30);
        $categories = MmCategory::query()->orderBy('name')->get(['id', 'code', 'name', 'is_active']);
        $units = MmUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $canManage = MmAuthorization::canManage($request->user());

        return view('mm05.specifications', compact('definitions', 'categories', 'units', 'canManage'));
    }

    public function storeDefinition(
        Request $request,
        MmAttributeDefinitionService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);

        $data = $request->only([
            'category_id',
            'code',
            'label',
            'data_type',
            'unit_id',
            'sort_order',
        ]);
        $data['is_required'] = $request->boolean('is_required');
        $service->createDefinition($data);

        return redirect()->route('mm05.specifications.index')
            ->with('message', 'Definisi spesifikasi berhasil ditambahkan.');
    }

    public function updateDefinition(
        Request $request,
        MmAttributeDefinition $definition,
        MmAttributeDefinitionService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);

        $data = $request->only([
            'category_id',
            'code',
            'label',
            'data_type',
            'unit_id',
            'sort_order',
        ]);
        $data['is_required'] = $request->boolean('is_required');
        $service->updateDefinition($definition, $data);

        return redirect()->route('mm05.specifications.index')
            ->with('message', 'Definisi spesifikasi berhasil diperbarui.');
    }

    public function deactivateDefinition(
        Request $request,
        MmAttributeDefinition $definition,
        MmAttributeDefinitionService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->deactivateDefinition($definition);

        return redirect()->route('mm05.specifications.index')
            ->with('message', 'Definisi spesifikasi dinonaktifkan dan tetap tersimpan sebagai referensi.');
    }

    public function variants(Request $request, MmReferencePriceSelector $referencePriceSelector): View
    {
        MmAuthorization::authorizeView($request);
        MmSafety::check();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', Rule::exists('mm_categories', 'id')],
            'material_id' => ['nullable', 'integer', Rule::exists('mm_materials', 'id')],
        ]);
        $canManage = MmAuthorization::canManage($request->user());

        $variantQuery = MmMaterialVariant::query()
            ->with(['material.category', 'baseUnit', 'attributes.definition.unit']);

        if (! $canManage) {
            $variantQuery
                ->where('is_active', true)
                ->whereHas('material', fn ($query) => $query->where('is_active', true));
        }

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $searchPattern = "%{$search}%";
            $booleanSearch = match (mb_strtolower($search)) {
                'ya', 'yes', 'true', '1' => true,
                'tidak', 'no', 'false', '0' => false,
                default => null,
            };

            $variantQuery->where(function ($query) use ($searchPattern, $booleanSearch): void {
                $query->whereHas('material', function ($materialQuery) use ($searchPattern): void {
                    $materialQuery
                        ->where('code', 'ilike', $searchPattern)
                        ->orWhere('name', 'ilike', $searchPattern);
                })
                    ->orWhere('sku', 'ilike', $searchPattern)
                    ->orWhere('variant_name', 'ilike', $searchPattern)
                    ->orWhereHas('attributes', function ($attributeQuery) use ($searchPattern, $booleanSearch): void {
                        $attributeQuery
                            ->where('value_text', 'ilike', $searchPattern)
                            ->orWhereRaw('CAST(value_number AS TEXT) ILIKE ?', [$searchPattern]);

                        if ($booleanSearch !== null) {
                            $attributeQuery->orWhere('value_boolean', $booleanSearch);
                        }
                    });
            });
        }

        if (isset($filters['category_id'])) {
            $variantQuery->whereHas(
                'material',
                fn ($query) => $query->where('category_id', $filters['category_id']),
            );
        }

        if (isset($filters['material_id'])) {
            $variantQuery->where('material_id', $filters['material_id']);
        }

        $variants = $variantQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        $referencePricesByVariant = $referencePriceSelector->latestEligiblePricesForVariants(
            $variants->getCollection()->modelKeys(),
            today()->toDateString(),
        );
        $categories = MmCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $materials = MmMaterial::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('code')
            ->get(['id', 'category_id', 'code', 'name']);
        $units = MmUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $definitionsByCategory = MmAttributeDefinition::query()
            ->with('unit')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->groupBy('category_id');

        return view('mm05.variants', compact(
            'variants',
            'referencePricesByVariant',
            'categories',
            'materials',
            'units',
            'definitionsByCategory',
            'canManage',
        ));
    }

    public function storeVariant(Request $request, MmVariantService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $service->createVariant(
            $request->only(['material_id', 'sku', 'variant_name', 'base_unit_id', 'specification_note']),
            (int) $request->user()->id,
        );

        return redirect()->route('mm05.variants.index')->with('message', 'Varian material berhasil ditambahkan.');
    }

    public function updateVariant(
        Request $request,
        MmMaterialVariant $variant,
        MmVariantService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->updateVariant(
            $variant,
            $request->only(['material_id', 'sku', 'variant_name', 'base_unit_id', 'specification_note']),
            (int) $request->user()->id,
        );

        return redirect()->route('mm05.variants.index')->with('message', 'Varian material berhasil diperbarui.');
    }

    public function deactivateVariant(
        Request $request,
        MmMaterialVariant $variant,
        MmVariantService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->deactivateVariant($variant, (int) $request->user()->id);

        return redirect()->route('mm05.variants.index')
            ->with('message', 'Varian dinonaktifkan dan tetap tersimpan sebagai referensi.');
    }

    public function updateMaterialVariantAttributes(
        Request $request,
        int $material,
        int $variant,
        MmVariantService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);

        $record = MmMaterial::query()->findOrFail($material);
        $variantRecord = MmMaterialVariant::query()
            ->whereKey($variant)
            ->where('material_id', $record->id)
            ->firstOrFail();

        $validated = $request->validate([
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['array'],
        ]);

        $service->setAttributes($variantRecord, $validated['attributes'] ?? []);

        return redirect()->route('mm05.materials.show', $record->id)
            ->with('message', 'Spesifikasi material berhasil diperbarui.');
    }

    public function setVariantAttributes(
        Request $request,
        MmMaterialVariant $variant,
        MmVariantService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $validated = $request->validate([
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['array'],
        ]);
        $service->setAttributes($variant, $validated['attributes'] ?? []);

        return redirect()->route('mm05.variants.index')
            ->with('message', 'Nilai spesifikasi varian berhasil disimpan.');
    }

    public function categoryDefinitions(Request $request, int $category): View
    {
        MmAuthorization::authorizeView($request);
        MmSafety::check();
        $record = MmCategory::query()->findOrFail($category);
        $definitions = MmAttributeDefinition::query()->with(['category', 'unit'])->where('category_id', $record->id)->orderBy('sort_order')->orderBy('id')->paginate(30);
        $categories = MmCategory::query()->orderBy('name')->get(['id', 'code', 'name', 'is_active']);
        $units = MmUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $canManage = MmAuthorization::canManage($request->user());
        return view('mm05.specifications', compact('definitions', 'categories', 'units', 'canManage', 'record'));
    }

    public function storeCategoryDefinition(Request $request, int $category, MmAttributeDefinitionService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $record = MmCategory::query()->findOrFail($category);
        $data = $request->only(['code', 'label', 'data_type', 'unit_id', 'sort_order']);
        $data['category_id'] = $record->id;
        $data['is_required'] = $request->boolean('is_required');
        $service->createDefinition($data);
        return redirect()->route('mm05.categories.specifications.index', $record->id)->with('message', 'Definisi spesifikasi berhasil ditambahkan.');
    }

    public function updateCategoryDefinition(Request $request, int $category, int $definition, MmAttributeDefinitionService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $record = MmCategory::query()->findOrFail($category);
        $attribute = MmAttributeDefinition::query()->where('category_id', $record->id)->findOrFail($definition);
        $data = $request->only(['code', 'label', 'data_type', 'unit_id', 'sort_order']);
        $data['is_required'] = $request->boolean('is_required');
        $service->updateDefinition($attribute, $data);
        return redirect()->route('mm05.categories.specifications.index', $record->id)->with('message', 'Definisi spesifikasi berhasil diperbarui.');
    }

    public function deactivateCategoryDefinition(Request $request, int $category, int $definition, MmAttributeDefinitionService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $record = MmCategory::query()->findOrFail($category);
        $attribute = MmAttributeDefinition::query()->where('category_id', $record->id)->findOrFail($definition);
        $service->deactivateDefinition($attribute);
        return redirect()->route('mm05.categories.specifications.index', $record->id)->with('message', 'Definisi spesifikasi dinonaktifkan; data historis tetap dipertahankan.');
    }


}
