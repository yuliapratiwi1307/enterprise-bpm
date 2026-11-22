<?php

namespace App\Http\Controllers;

use App\Models\MmMaterial;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmSupplierQuote;
use App\Models\MmUnit;
use App\Models\User;
use App\Services\MmAuthorization;
use App\Services\MmSafety;
use App\Services\MmSupplierQuoteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmSupplierQuoteController extends Controller
{
    public function index(Request $request): View
    {
        MmAuthorization::authorizeView($request);
        MmSafety::check();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'material_id' => ['nullable', 'integer', Rule::exists('mm_materials', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('mm_suppliers', 'id')],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'expired'])],
        ]);
        $query = MmSupplierQuote::query()
            ->with([
                'variant.material.category',
                'supplier',
                'priceUnit',
                'approvedBy:id,name',
                'rejectedBy:id,name',
            ])
            ->orderByDesc('quoted_on')
            ->orderByDesc('id');

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $pattern = "%{$search}%";
            $query->where(function ($builder) use ($pattern): void {
                $builder->where('supplier_reference', 'ilike', $pattern)
                    ->orWhereHas('variant', function ($variantQuery) use ($pattern): void {
                        $variantQuery
                            ->where('sku', 'ilike', $pattern)
                            ->orWhere('variant_name', 'ilike', $pattern)
                            ->orWhereHas('material', fn ($materialQuery) => $materialQuery
                                ->where('code', 'ilike', $pattern)
                                ->orWhere('name', 'ilike', $pattern));
                    })
                    ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery
                        ->where('code', 'ilike', $pattern)
                        ->orWhere('name', 'ilike', $pattern));
            });
        }

        if (isset($filters['material_id'])) {
            $query->whereHas(
                'variant',
                fn ($variantQuery) => $variantQuery->where('material_id', $filters['material_id']),
            );
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->where('is_active', true)
                ->where(fn ($builder) => $builder
                    ->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', today()->toDateString()));
        } elseif (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        } elseif (($filters['status'] ?? null) === 'expired') {
            $query->where('is_active', true)
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '<', today()->toDateString());
        }

        $records = $query->paginate(20)->withQueryString();
        $materials = MmMaterial::query()->with('category')->orderBy('code')->get();
        $variants = MmMaterialVariant::query()
            ->with('material.category')
            ->where('is_active', true)
            ->whereHas('material', fn ($builder) => $builder->where('is_active', true))
            ->orderBy('sku')
            ->get();
        $suppliers = MmSupplier::query()->orderBy('name')->get();
        $activeSuppliers = $suppliers->where('is_active', true);
        $units = MmUnit::query()->where('is_active', true)->orderBy('name')->get();
        $canManage = MmAuthorization::canManage($request->user());
        $canApproveQuotes = MmAuthorization::canApproveQuotes($request->user());

        return view('mm05.supplier-quotes', compact(
            'records',
            'materials',
            'variants',
            'suppliers',
            'activeSuppliers',
            'units',
            'canManage',
            'canApproveQuotes',
        ));
    }

    public function store(Request $request, MmSupplierQuoteService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $service->recordQuote(
            $request->only([
                'material_variant_id',
                'supplier_id',
                'price_unit_id',
                'amount',
                'currency',
                'quoted_on',
                'valid_until',
                'supplier_reference',
                'source_document_path',
                'notes',
            ]),
            (int) $request->user()->id,
        );

        return redirect()->route('mm05.quotes.index')
            ->with('message', 'Penawaran tersimpan dengan status menunggu persetujuan.');
    }

    public function update(
        Request $request,
        MmSupplierQuote $quote,
        MmSupplierQuoteService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->updateQuote($quote, $request->only([
            'price_unit_id',
            'amount',
            'currency',
            'quoted_on',
            'valid_until',
            'supplier_reference',
            'source_document_path',
            'notes',
        ]));

        return redirect()->route('mm05.quotes.index')
            ->with('message', 'Penawaran berhasil diperbarui.');
    }

    public function approve(
        Request $request,
        MmSupplierQuote $quote,
        MmSupplierQuoteService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeQuoteApproval($request);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $service->approveQuote($quote, $actor, $validated['reason'] ?? null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('mm05.quotes.index')
            ->with('message', 'Penawaran berhasil disetujui.');
    }

    public function reject(
        Request $request,
        MmSupplierQuote $quote,
        MmSupplierQuoteService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeQuoteApproval($request);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $service->rejectQuote($quote, $actor, $validated['reason']);

        return redirect()->route('mm05.quotes.index')
            ->with('message', 'Penawaran berhasil ditolak.');
    }

    public function deactivate(
        Request $request,
        MmSupplierQuote $quote,
        MmSupplierQuoteService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $service->setQuoteActive($quote, false, $actor, $validated['reason'] ?? null);

        return redirect()->route('mm05.quotes.index')
            ->with('message', 'Penawaran dinonaktifkan dan tetap tersimpan dalam histori.');
    }

    public function activate(
        Request $request,
        MmSupplierQuote $quote,
        MmSupplierQuoteService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $service->setQuoteActive($quote, true, $actor, $validated['reason'] ?? null);

        return redirect()->route('mm05.quotes.index')
            ->with('message', 'Penawaran diaktifkan kembali.');
    }
}
