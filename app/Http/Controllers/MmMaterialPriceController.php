<?php

namespace App\Http\Controllers;

use App\Models\MmMaterialPrice;
use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use App\Models\MmSupplierQuote;
use App\Services\MmAuthorization;
use App\Services\MmMaterialPriceService;
use App\Services\MmSafety;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MmMaterialPriceController extends Controller
{
    public function index(Request $request): View
    {
        MmAuthorization::authorizeView($request);
        MmSafety::check();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha:ascii'],
        ]);

        $query = MmMaterialPrice::query()
            ->with([
                'variant.material',
                'sourceQuote.supplier',
                'supplier',
                'priceUnit',
            ])
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $pattern = "%{$search}%";
            $query->where(function ($builder) use ($pattern): void {
                $builder->where('source_name', 'ilike', $pattern)
                    ->orWhereHas('variant', fn ($variant) => $variant
                        ->where('sku', 'ilike', $pattern)
                        ->orWhereHas('material', fn ($material) => $material
                            ->where('code', 'ilike', $pattern)
                            ->orWhere('name', 'ilike', $pattern)))
                    ->orWhereHas('sourceQuote', fn ($quote) => $quote
                        ->where('supplier_reference', 'ilike', $pattern)
                        ->orWhereHas('supplier', fn ($supplier) => $supplier
                            ->where('code', 'ilike', $pattern)
                            ->orWhere('name', 'ilike', $pattern)));
            });
        }

        if (!empty($filters['currency'])) {
            $query->where('currency', strtoupper($filters['currency']));
        }

        $records = $query->paginate(20)->withQueryString();

        $eligibleQuotes = MmSupplierQuote::query()
            ->with(['variant.material', 'supplier', 'priceUnit'])
            ->where('is_active', true)
            ->where('approval_status', MmSupplierQuote::STATUS_APPROVED)
            ->whereDate('quoted_on', '<=', today()->toDateString())
            ->where(fn ($builder) => $builder
                ->whereNull('valid_until')
                ->orWhereDate('valid_until', '>=', today()->toDateString()))
            ->whereHas('supplier', fn ($supplier) => $supplier->where('is_active', true))
            ->whereHas('variant', fn ($variant) => $variant
                ->where('is_active', true)
                ->whereHas('material', fn ($material) => $material->where('is_active', true)))
            ->orderByDesc('quoted_on')
            ->orderByDesc('id')
            ->get();

        $materials = MmMaterial::query()->with('category')->where('is_active', true)->orderBy('name')->get(['id', 'category_id', 'code', 'name']);
        $variants = MmMaterialVariant::query()->with('material.category')->where('is_active', true)
            ->whereHas('material', fn ($q) => $q->where('is_active', true))->orderBy('sku')->get();
        $hasMultipleVariants = $variants->groupBy('material_id')->contains(fn ($group) => $group->count() > 1);
        $suppliers = MmSupplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $units = MmUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'usage_group']);

        return view('mm05.material-prices', [
            'records' => $records,
            'materials' => $materials,
            'variants' => $variants,
            'hasMultipleVariants' => $hasMultipleVariants,
            'suppliers' => $suppliers,
            'units' => $units,
            'eligibleQuotes' => $eligibleQuotes,
            'canManage' => MmAuthorization::canManage($request->user()),
        ]);
    }

    public function store(
        Request $request,
        MmMaterialPriceService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);

        $service->recordPrice(
            $request->only(['source_quote_id', 'effective_from', 'effective_until', 'change_reason']),
            (int) $request->user()->id,
        );

        return redirect()->route('mm05.prices.index')
            ->with('message', 'Referensi harga berhasil dicatat dari penawaran yang disetujui. Histori sebelumnya tetap tersimpan.');
    }


    /**
     * Record a direct supplier price update from the material workspace.
     * Historical rows are retained by MmMaterialPriceService.
     */
    public function storeDirect(
        Request $request,
        MmMaterialPriceService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);

        $data = $request->validate([
            'material_id' => ['required', 'integer', 'exists:mm_materials,id'],
            'material_variant_id' => ['nullable', 'integer', 'exists:mm_material_variants,id'],
            'supplier_id' => ['required', 'integer', 'exists:mm_suppliers,id'],
            'price_unit_id' => ['required', 'integer', 'exists:mm_units,id'],
            'conversion_factor' => ['nullable', 'numeric', 'gt:0', 'max:999999999999'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999999'],
            'currency' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'communication_method' => ['required', 'in:WhatsApp,Email,Telepon'],
            'communication_note' => ['nullable', 'string', 'max:1800'],
            'change_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $variantQuery = \App\Models\MmMaterialVariant::query()
            ->where('material_id', (int) $data['material_id'])
            ->where('is_active', true);

        if (!empty($data['material_variant_id'])) {
            $variantQuery->whereKey((int) $data['material_variant_id']);
        }

        $variants = $variantQuery->orderBy('id')->limit(2)->get();
        if ($variants->isEmpty()) {
            return back()->withErrors(['material_id' => 'Material belum memiliki varian aktif untuk dicatat harganya.'])->withInput();
        }
        if ($variants->count() > 1 && empty($data['material_variant_id'])) {
            return back()->withErrors(['material_variant_id' => 'Material memiliki beberapa varian. Pilih varian material yang akan diberi harga.'])->withInput();
        }

        $communication = 'Konfirmasi melalui '.$data['communication_method'].'.';
        $note = trim((string) ($data['communication_note'] ?? ''));
        $reason = trim($communication.' '.($note !== '' ? $note : (string) ($data['change_reason'] ?? '')));
        $service->updateSupplierPrice([
            'supplier_id' => $data['supplier_id'],
            'price_unit_id' => $data['price_unit_id'],
            'conversion_factor' => $data['conversion_factor'] ?? null,
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'effective_from' => $data['effective_from'],
            'change_reason' => $reason,
        ], (int) $variants->first()->id, (int) $request->user()->id);

        return redirect()->route('mm05.prices.index')
            ->with('message', 'Harga supplier berhasil disimpan. Harga sebelumnya tetap tersedia dalam riwayat.');
    }

    public function updateFromMaterial(
        Request $request,
        int $material,
        int $variant,
        MmMaterialPriceService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);

        $record = \App\Models\MmMaterial::query()->findOrFail($material);
        abort_unless($record->variants()->whereKey($variant)->exists(), 404);

        $service->updateSupplierPrice(
            $request->only(['supplier_id', 'price_unit_id', 'amount', 'currency', 'effective_from', 'change_reason']),
            $variant,
            (int) $request->user()->id,
        );

        return redirect()->route('mm05.materials.show', $material)
            ->with('message', 'Harga supplier berhasil diperbarui. Harga sebelumnya tersimpan dalam riwayat.');
    }

}
