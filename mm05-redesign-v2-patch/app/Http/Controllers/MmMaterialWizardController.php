<?php

namespace App\Http\Controllers;

use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use App\Services\MmAuthorization;
use App\Services\MmMasterDataService;
use App\Services\MmMaterialPriceService;
use App\Services\MmSafety;
use App\Services\MmVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MmMaterialWizardController extends Controller
{
    public function create(Request $request)
    {
        MmAuthorization::authorizeManage($request);
        MmSafety::check();

        return view('mm05.material-wizard', [
            'categories' => MmCategory::where('is_active', true)->orderBy('name')->get(),
            'units' => MmUnit::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => MmSupplier::where('is_active', true)->orderBy('name')->get(),
            'definitions' => MmAttributeDefinition::where('is_active', true)->with('unit')->orderBy('sort_order')->get()->groupBy('category_id'),
        ]);
    }

    public function store(
        Request $request,
        MmMasterDataService $master,
        MmVariantService $variants,
        MmMaterialPriceService $prices
    ) {
        MmAuthorization::authorizeManage($request);
        MmSafety::check();

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:mm_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.variant_name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['required', 'string', 'max:90', 'distinct', 'unique:mm_material_variants,sku'],
            'variants.*.base_unit_id' => ['required', 'integer', 'exists:mm_units,id'],
            'variants.*.specification_note' => ['nullable', 'string'],
            'supplier_mode' => ['required', 'in:existing,new,none'],
            'supplier_id' => ['nullable', 'required_if:supplier_mode,existing', 'integer', 'exists:mm_suppliers,id'],
            'new_supplier_name' => ['nullable', 'required_if:supplier_mode,new', 'string', 'max:255'],
            'new_supplier_contact' => ['nullable', 'string', 'max:150'],
            'new_supplier_phones' => ['nullable', 'array'],
            'new_supplier_phones.*' => ['nullable', 'string', 'max:60'],
            'price_amount' => ['nullable', 'numeric', 'gt:0'],
            'price_unit_id' => ['nullable', 'required_with:price_amount', 'integer', 'exists:mm_units,id'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha:ascii', 'required_with:price_amount'],
            'effective_from' => ['nullable', 'date_format:Y-m-d', 'required_with:price_amount'],
            'communication_method' => ['nullable', 'in:WhatsApp,Telepon,Email,Meeting,Lainnya'],
            'communication_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['supplier_mode'] === 'none' && ! empty($data['price_amount'])) {
            throw ValidationException::withMessages(['price_amount' => 'Pilih supplier sebelum mencatat harga.']);
        }
        if ($data['supplier_mode'] !== 'none' && empty($data['price_amount'])) {
            throw ValidationException::withMessages(['price_amount' => 'Isi harga supplier, atau pilih Belum menentukan supplier jika belum ada harga.']);
        }
        if ($data['supplier_mode'] === 'new' && ! empty($data['price_amount']) &&
            empty(array_filter($data['new_supplier_phones'] ?? []))) {
            throw ValidationException::withMessages(['new_supplier_phones' => 'Isi minimal satu nomor kontak supplier untuk pencatatan harga.']);
        }
        if ($data['supplier_mode'] !== 'none' && ! empty($data['price_amount']) &&
            $data['supplier_mode'] === 'existing' && empty($data['supplier_id'])) {
            throw ValidationException::withMessages(['supplier_id' => 'Pilih supplier yang terdaftar.']);
        }

        $actor = (int) $request->user()->id;
        $material = DB::transaction(function () use ($data, $master, $variants, $prices, $actor) {
            // Keep MAT sequence allocation safe for concurrent PostgreSQL submissions.
            DB::statement('LOCK TABLE mm_materials IN EXCLUSIVE MODE');
            $materialCode = $this->nextMaterialCode();

            $material = $master->createMaterial([
                'category_id' => $data['category_id'],
                'code' => $materialCode,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ], $actor);

            $attributeRows = [];
            foreach (($data['attributes'] ?? []) as $definitionId => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $definition = MmAttributeDefinition::whereKey($definitionId)
                    ->where('category_id', $material->category_id)
                    ->where('is_active', true)->first();
                if (! $definition) {
                    throw ValidationException::withMessages(["attributes.$definitionId" => 'Atribut tidak valid untuk kategori ini.']);
                }
                $row = [
                    'attribute_definition_id' => (int) $definitionId,
                    'value_text' => null, 'value_number' => null, 'value_boolean' => null,
                ];
                if ($definition->data_type === 'number') {
                    $row['value_number'] = $value;
                } elseif ($definition->data_type === 'boolean') {
                    $row['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $row['value_text'] = $value;
                }
                $attributeRows[] = $row;
            }

            $createdVariants = [];
            foreach ($data['variants'] as $variantData) {
                $variant = $variants->createVariant([
                    'material_id' => $material->id,
                    'sku' => $variantData['sku'],
                    'variant_name' => $variantData['variant_name'],
                    'base_unit_id' => $variantData['base_unit_id'],
                    'specification_note' => $variantData['specification_note'] ?? null,
                ], $actor);
                if ($attributeRows) {
                    $variants->setAttributes($variant, $attributeRows);
                }
                $createdVariants[] = $variant;
            }

            $supplierId = $data['supplier_id'] ?? null;
            if ($data['supplier_mode'] === 'new') {
                $phones = array_values(array_filter(array_map('trim', $data['new_supplier_phones'] ?? [])));
                $supplier = $master->createSupplier([
                    'name' => $data['new_supplier_name'],
                    'contact_name' => $data['new_supplier_contact'] ?? null,
                    'phone' => $phones[0] ?? null,
                    'email' => null, 'notes' => null,
                ]);
                $supplierId = $supplier->id;

                // Store any additional contact numbers without overloading the supplier's main phone field.
                foreach (array_slice($phones, 1) as $phone) {
                    DB::table('mm_supplier_contacts')->insert([
                        'supplier_id' => $supplierId,
                        'phone' => $phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Initial direct Procurement price is attached to the first (main) variant.
            if (! empty($data['price_amount'])) {
                $method = $data['communication_method'] ?? 'Lainnya';
                $reason = 'Konfirmasi melalui '.$method.'. '.($data['communication_note'] ?? '');
                $prices->updateSupplierPrice([
                    'supplier_id' => $supplierId,
                    'price_unit_id' => $data['price_unit_id'],
                    'amount' => $data['price_amount'],
                    'currency' => $data['currency'],
                    'effective_from' => $data['effective_from'],
                    'change_reason' => trim($reason),
                ], (int) $createdVariants[0]->id, $actor);
            }

            return $material;
        });

        return redirect()->route('mm05.materials.show', $material->id)
            ->with('message', 'Material berhasil dibuat. Kode material: '.$material->code);
    }

    /**
     * Allocate the next MAT code. Called while mm_materials is locked in the
     * surrounding transaction, so two wizard submissions cannot allocate the same code.
     */
    private function nextMaterialCode(): string
    {
        $highest = 0;

        foreach (DB::table('mm_materials')->pluck('code') as $existingCode) {
            if (preg_match('/\\AMAT-(\\d+)\\z/', (string) $existingCode, $matches) !== 1) {
                continue;
            }

            $number = (int) $matches[1];
            if ($number > $highest) {
                $highest = $number;
            }
        }

        return 'MAT-'.str_pad((string) ($highest + 1), 6, '0', STR_PAD_LEFT);
    }
}
