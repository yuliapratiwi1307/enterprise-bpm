<?php

namespace App\Http\Controllers;

use App\Models\MmCategory;
use App\Models\MmUnit;
use App\Services\MmAuthorization;
use App\Services\MmMasterDataService;
use App\Services\MmSafety;
use Illuminate\Http\Request;

class MmMasterDataController extends Controller
{
    private function authorizeView(Request $request): void
    {
        MmAuthorization::authorizeView($request);
    }

    private function authorizeManage(Request $request): void
    {
        MmAuthorization::authorizeManage($request);
    }

    public function categories(Request $request)
    {
        $this->authorizeView($request);
        MmSafety::check();

        $query = MmCategory::query()->with('parent')->orderBy('name')->orderBy('id');
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($builder) => $builder
                ->where('code', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%"));
        }
        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $records = $query->paginate(20)->withQueryString();
        $parents = MmCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $canManage = MmAuthorization::canManage($request->user());

        return view('mm05.categories', compact('records', 'parents', 'canManage'));
    }

    public function storeCategory(Request $request, MmMasterDataService $service)
    {
        $this->authorizeManage($request);
        $service->createCategory($request->only(['code', 'name', 'parent_id']));

        return redirect()->route('mm05.categories.index')->with('message', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, int $category, MmMasterDataService $service)
    {
        $this->authorizeManage($request);
        $service->updateCategory($category, $request->only(['code', 'name', 'parent_id']));

        return redirect()->route('mm05.categories.index')->with('message', 'Kategori berhasil diperbarui.');
    }

    public function deactivateCategory(Request $request, int $category, MmMasterDataService $service)
    {
        $this->authorizeManage($request);
        $record = MmCategory::query()->findOrFail($category);
        $service->deactivateCategory($record);

        return redirect()->route('mm05.categories.index')->with('message', 'Kategori berhasil dinonaktifkan. Data tidak dihapus.');
    }

    public function units(Request $request)
    {
        $this->authorizeView($request);
        MmSafety::check();

        $query = MmUnit::query()->orderBy('name')->orderBy('id');
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($builder) => $builder
                ->where('code', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%")
                ->orWhere('dimension', 'ilike', "%{$search}%"));
        }
        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $records = $query->paginate(20)->withQueryString();
        $canManage = MmAuthorization::canManage($request->user());

        return view('mm05.units', compact('records', 'canManage'));
    }

    public function storeUnit(Request $request, MmMasterDataService $service)
    {
        $this->authorizeManage($request);
        $service->createUnit($request->only(['name', 'dimension', 'unit_usage']));

        return redirect()->route('mm05.units.index')->with('message', 'Satuan berhasil ditambahkan.');
    }

    public function updateUnit(Request $request, int $unit, MmMasterDataService $service)
    {
        $this->authorizeManage($request);
        $record = MmUnit::query()->findOrFail($unit);
        $service->updateUnit($record, $request->only(['name', 'dimension', 'unit_usage']));

        return redirect()->route('mm05.units.index')->with('message', 'Satuan berhasil diperbarui.');
    }

    public function deactivateUnit(Request $request, int $unit, MmMasterDataService $service)
    {
        $this->authorizeManage($request);
        $record = MmUnit::query()->findOrFail($unit);
        $service->deactivateUnit($record);

        return redirect()->route('mm05.units.index')->with('message', 'Satuan berhasil dinonaktifkan. Data tidak dihapus.');
    }
}
