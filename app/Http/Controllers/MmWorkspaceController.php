<?php

namespace App\Http\Controllers;

use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use App\Services\MmAuthorization;
use App\Services\MmMasterDataService;
use App\Services\MmSafety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MmWorkspaceController extends Controller
{
    private function authorizeView(Request $request): void
    {
        MmAuthorization::authorizeView($request);
    }

    private function authorizeManage(Request $request): void
    {
        MmAuthorization::authorizeManage($request);
    }

    public function index(Request $request)
    {
        $this->authorizeView($request);
        MmSafety::check();

        $counts = [
            'materials' => DB::table('mm_materials')->where('is_active', true)->count(),
            'categories' => DB::table('mm_categories')->where('is_active', true)->count(),
            'suppliers' => DB::table('mm_suppliers')->where('is_active', true)->count(),
            'variants' => DB::table('mm_material_variants')->where('is_active', true)->count(),
            'quotes' => DB::table('mm_supplier_quotes')->count(),
            'prices' => DB::table('mm_material_prices')->count(),
        ];

        $recentMaterials = DB::table('mm_materials as m')
            ->leftJoin('mm_categories as c', 'c.id', '=', 'm.category_id')
            ->select('m.id', 'm.code', 'm.name', 'm.is_active', 'm.updated_at', 'c.name as category_name')
            ->orderByDesc('m.updated_at')->orderByDesc('m.id')->limit(8)->get();

        return view('mm05.index', compact('counts', 'recentMaterials'));
    }

    public function materials(Request $request)
    {
        $this->authorizeView($request);
        MmSafety::check();

        $query = MmMaterial::query()->with('category')->orderByDesc('id');

        if ($search = trim((string) $request->query('q', ''))) {
            // Setiap kata boleh cocok di kolom atau relasi berbeda.
            // Semua kata harus cocok pada material yang sama.
            $words = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);


            // Filter spesifikasi: angka dan satuan harus berada
            // pada atribut yang sama, bukan atribut terpisah.
            $remainingSearch = preg_replace_callback(
                '/(?<![\pL\pN])(\d+(?:[.,]\d+)?)\s*(mm|cm)\b/iu',
                function ($matches) use ($query) {
                    $number = str_replace(',', '.', $matches[1]);
                    $unitName = mb_strtolower($matches[2]);

                    $query->whereHas('variants.attributes', function ($attributes) use ($number, $unitName) {
                        $attributes
                            ->where(function ($values) use ($number) {
                                $values->where('value_text', $number)
                                    ->orWhereRaw(
                                        'CAST(value_number AS TEXT) = ?',
                                        [$number]
                                    );
                            })
                            ->whereHas('definition', function ($definition) use ($unitName) {
                                $definition
                                    ->whereHas('unit', fn ($unit) =>
                                        $unit->whereRaw(
                                            'LOWER(name) = ?',
                                            [$unitName]
                                        )
                                    );

                                if ($unitName === 'mm') {
                                    $definition->whereRaw(
                                        'LOWER(label) = ?',
                                        ['ketebalan']
                                    );
                                } elseif ($unitName === 'cm') {
                                    $definition->whereRaw(
                                        'LOWER(label) IN (?, ?)',
                                        ['panjang', 'lebar']
                                    );
                                }
                            });
                    });

                    return ' ';
                },
                $search
            );

            // Kata selain pasangan angka+satuan tetap dicari
            // melalui kode, nama, supplier, warna, dan relasi lain.
            $words = preg_split(
                '/\s+/u',
                trim($remainingSearch),
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            foreach ($words as $word) {
                $term = '%'.addcslashes($word, '%_\\').'%';

                $query->where(function ($builder) use ($term, $word) {
                    $builder->where('mm_materials.code', 'ilike', $term)
                        ->orWhere('mm_materials.name', 'ilike', $term)
                        ->orWhere('mm_materials.description', 'ilike', $term)
                        ->orWhereHas('category', fn ($q) =>
                            $q->where('name', 'ilike', $term)
                        )
                        ->orWhereHas('variants', function ($variants) use ($term) {
                            $variants->where('sku', 'ilike', $term)
                                ->orWhere('variant_name', 'ilike', $term)
                                ->orWhereHas('baseUnit', fn ($q) =>
                                    $q->where('name', 'ilike', $term)
                                )
                                ->orWhereHas('attributes', function ($attributes) use ($term) {
                                    $attributes->where('value_text', 'ilike', $term)
                                        ->orWhereRaw(
                                            'CAST(value_number AS TEXT) ILIKE ?',
                                            [$term]
                                        )
                                        ->orWhereHas('definition', function ($definitions) use ($term) {
                                            $definitions->where('label', 'ilike', $term)
                                                ->orWhereHas('unit', fn ($q) =>
                                                    $q->where('name', 'ilike', $term)
                                                );
                                        });
                                })
                                ->orWhereHas('prices', function ($prices) use ($term) {
                                    $prices->whereRaw(
                                        'CAST(amount AS TEXT) ILIKE ?',
                                        [$term]
                                    )
                                        ->orWhere('currency', 'ilike', $term)
                                        ->orWhereRaw(
                                            "TO_CHAR(effective_from, 'DD/MM/YYYY') ILIKE ?",
                                            [$term]
                                        )
                                        ->orWhereHas('supplier', fn ($q) =>
                                            $q->where('name', 'ilike', $term)
                                        )
                                        ->orWhereHas('priceUnit', fn ($q) =>
                                            $q->where('name', 'ilike', $term)
                                        );
                                });
                        });

                    if (mb_strtolower($word) === 'aktif') {
                        $builder->orWhere('mm_materials.is_active', true);
                    }

                    if (mb_strtolower($word) === 'nonaktif') {
                        $builder->orWhere('mm_materials.is_active', false);
                    }
                });
            }
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $records = $query->paginate(10)->withQueryString();

        // Muat informasi daftar hanya untuk 20 material pada halaman ini.
        $records->getCollection()->load([
            'variants.attributes.definition.unit',
            'variants.prices.supplier',
            'variants.prices.priceUnit',
        ]);

        foreach ($records->getCollection() as $materialRecord) {
            $variants = $materialRecord->variants;
            $specificationVariant = $variants->first(fn ($variant) => $variant->attributes->isNotEmpty())
                ?? $variants->first();
            $latestPrice = $variants->flatMap(fn ($variant) => $variant->prices)
                ->sort(function ($a, $b) {
                    $dateComparison = strcmp((string) $b->effective_from, (string) $a->effective_from);
                    return $dateComparison !== 0 ? $dateComparison : ($b->id <=> $a->id);
                })->first();
            $materialRecord->setRelation('list_specification_variant', $specificationVariant);
            $materialRecord->setRelation('list_latest_price', $latestPrice);
        }
        $categories = MmCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $canManage = MmAuthorization::canManage($request->user());

        return view('mm05.materials', compact('records', 'categories', 'canManage'));
    }

    public function showMaterial(Request $request, int $material)
    {
        $this->authorizeView($request);
        MmSafety::check();

        $record = MmMaterial::query()
            ->with([
                'category',
                'variants' => fn ($query) => $query->orderBy('sku'),
                'variants.baseUnit',
                'variants.attributes.definition.unit',
                'variants.supplierQuotes' => fn ($query) => $query->with(['supplier', 'priceUnit'])->orderByDesc('quoted_on'),
                'variants.prices' => fn ($query) => $query->with(['priceUnit', 'supplier'])->orderByDesc('effective_from'),
            ])
            ->findOrFail($material);

        $definitions = MmAttributeDefinition::query()
            ->where('category_id', $record->category_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $suppliers = MmSupplier::query()->where('is_active', true)->orderBy('name')->get();
        $units = MmUnit::query()->where('is_active', true)->orderBy('name')->get();
        $canManage = MmAuthorization::canManage($request->user());

        $editCategories = MmCategory::query()->where(function ($q) use ($record) {
            $q->where('is_active', true)->orWhereKey($record->category_id);
        })->orderBy('name')->get(['id', 'name']);

        return view('mm05.material-detail', compact('record', 'definitions', 'suppliers', 'units', 'canManage', 'editCategories'));
    }

    public function storeMaterial(Request $request, MmMasterDataService $service)
    {
        $this->authorizeManage($request);

        $service->createMaterial(
            $request->only(['category_id', 'code', 'name', 'description']),
            (int) $request->user()->id
        );

        return redirect()->route('mm05.materials.index')
            ->with('message', 'Material berhasil ditambahkan.');
    }

    public function updateMaterial(Request $request, int $material, MmMasterDataService $service)
    {
        $this->authorizeManage($request);

        $record = MmMaterial::query()->findOrFail($material);
        $service->updateMaterial(
            $record,
            $request->only(['category_id', 'code', 'name', 'description']),
            (int) $request->user()->id
        );

        return redirect()->route('mm05.materials.show', $record->id)
            ->with('message', 'Material berhasil diperbarui.');
    }

    public function activateMaterial(Request $request, int $material, MmMasterDataService $service)
    {
        $this->authorizeManage($request);

        $record = MmMaterial::query()->findOrFail($material);
        $service->activateMaterial($record, (int) $request->user()->id);

        return redirect()->route('mm05.materials.show', $record->id)
            ->with('message', 'Material berhasil diaktifkan kembali.');
    }

    public function deactivateMaterial(Request $request, int $material, MmMasterDataService $service)
    {
        $this->authorizeManage($request);

        $record = MmMaterial::query()->findOrFail($material);
        $service->deactivateMaterial($record, (int) $request->user()->id);

        return redirect()->route('mm05.materials.index')
            ->with('message', 'Material berhasil dinonaktifkan. Data tidak dihapus.');
    }
}
