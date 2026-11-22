<?php

namespace App\Http\Controllers;

use App\Models\MmSupplier;
use App\Services\MmAuthorization;
use App\Services\MmMasterDataService;
use App\Services\MmSafety;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MmSupplierController extends Controller
{
    public function index(Request $request): View
    {
        MmAuthorization::authorizeView($request);
        MmSafety::check();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $query = MmSupplier::query()->orderBy('name')->orderBy('id');

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $query->where(fn ($builder) => $builder
                ->where('code', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%"));
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }

        $records = $query->paginate(20)->withQueryString();
        $canManage = MmAuthorization::canManage($request->user());

        return view('mm05.suppliers', compact('records', 'canManage'));
    }

    public function store(Request $request, MmMasterDataService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $service->createSupplier($request->only([
            'name',
            'contact_name',
            'phone',
            'email',
            'notes',
        ]));

        return redirect()->route('mm05.suppliers.index')
            ->with('message', 'Supplier berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        MmSupplier $supplier,
        MmMasterDataService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->updateSupplier($supplier, $request->only([
            'name',
            'contact_name',
            'phone',
            'email',
            'notes',
        ]));

        return redirect()->route('mm05.suppliers.index')
            ->with('message', 'Supplier berhasil diperbarui.');
    }

    public function deactivate(
        Request $request,
        MmSupplier $supplier,
        MmMasterDataService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->deactivateSupplier($supplier);

        return redirect()->route('mm05.suppliers.index')
            ->with('message', 'Supplier dinonaktifkan. Data dan histori terkait tetap tersimpan.');
    }

    public function activate(
        Request $request,
        MmSupplier $supplier,
        MmMasterDataService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->activateSupplier($supplier);

        return redirect()->route('mm05.suppliers.index')
            ->with('message', 'Supplier diaktifkan kembali.');
    }


    public function storeFromMaterial(
        Request $request,
        int $material,
        MmMasterDataService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        \App\Models\MmMaterial::query()->findOrFail($material);

        $service->createSupplier($request->only([
            'name', 'contact_name', 'phone', 'email', 'notes',
        ]));

        return redirect()->route('mm05.materials.show', $material)
            ->with('message', 'Supplier berhasil ditambahkan dan siap dipilih untuk input harga.');
    }

}
