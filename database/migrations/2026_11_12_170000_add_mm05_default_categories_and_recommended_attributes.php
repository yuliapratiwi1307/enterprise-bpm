<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, array{name: string, attributes: array<string, string>}> */
    private array $defaults = [
        'CAT-001' => [
            'name' => 'Bahan Baku Produksi',
            'attributes' => ['GRADE' => 'Grade', 'UKURAN' => 'Ukuran', 'KETEBALAN' => 'Ketebalan', 'PANJANG' => 'Panjang', 'LEBAR' => 'Lebar', 'BERAT' => 'Berat', 'WARNA' => 'Warna'],
        ],
        'CAT-002' => [
            'name' => 'Barang Pendukung Produksi',
            'attributes' => ['JENIS' => 'Jenis', 'MEREK' => 'Merek', 'MODEL' => 'Model', 'UKURAN' => 'Ukuran', 'SPESIFIKASI_TEKNIS' => 'Spesifikasi teknis'],
        ],
        'CAT-003' => [
            'name' => 'Bahan Kemasan',
            'attributes' => ['JENIS_BAHAN' => 'Jenis bahan', 'PANJANG' => 'Panjang', 'LEBAR' => 'Lebar', 'KETEBALAN' => 'Ketebalan', 'WARNA' => 'Warna', 'KAPASITAS' => 'Kapasitas'],
        ],
        'CAT-004' => [
            'name' => 'Sparepart / Suku Cadang Mesin',
            'attributes' => ['MEREK' => 'Merek', 'MODEL' => 'Model', 'TIPE' => 'Tipe', 'NOMOR_PART' => 'Nomor part', 'UKURAN' => 'Ukuran', 'KOMPATIBILITAS_MESIN' => 'Kompatibilitas mesin'],
        ],
        'CAT-005' => [
            'name' => 'Barang Operasional Kantor',
            'attributes' => ['MEREK' => 'Merek', 'MODEL' => 'Model', 'UKURAN' => 'Ukuran', 'TIPE' => 'Tipe', 'WARNA' => 'Warna'],
        ],
        'CAT-006' => [
            'name' => 'Barang Jadi / Produk',
            'attributes' => ['MODEL' => 'Model', 'TIPE' => 'Tipe', 'UKURAN' => 'Ukuran', 'WARNA' => 'Warna', 'BERAT' => 'Berat', 'KEMASAN' => 'Kemasan'],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->defaults as $code => $definition) {
                $categoryId = null;
                $category = DB::table('mm_categories')->where('code', $code)->first();
                if ($category !== null && mb_strtolower(trim($category->name)) !== mb_strtolower($definition['name'])) {
                    throw new RuntimeException("Category code {$code} is already assigned to a different category.");
                }

                if ($category === null) {
                    $category = DB::table('mm_categories')
                        ->whereRaw('LOWER(BTRIM(name)) = ?', [mb_strtolower($definition['name'])])
                        ->orderBy('id')
                        ->first();

                    if ($category !== null) {
                        DB::table('mm_categories')->where('id', $category->id)->update([
                            'code' => $code,
                            'name' => $definition['name'],
                            'updated_at' => now(),
                        ]);
                    } else {
                        $categoryId = DB::table('mm_categories')->insertGetId([
                            'code' => $code,
                            'name' => $definition['name'],
                            'parent_id' => null,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $categoryId ??= (int) $category->id;

                $sortOrder = 0;
                foreach ($definition['attributes'] as $attributeCode => $label) {
                    $exists = DB::table('mm_attribute_definitions')
                        ->where('category_id', $categoryId)
                        ->whereRaw('LOWER(BTRIM(label)) = ?', [mb_strtolower($label)])
                        ->exists();
                    if ($exists) {
                        $sortOrder++;

                        continue;
                    }

                    $codeCandidate = $attributeCode;
                    $suffix = 2;
                    while (DB::table('mm_attribute_definitions')
                        ->where('category_id', $categoryId)
                        ->where('code', $codeCandidate)
                        ->exists()) {
                        $codeCandidate = $attributeCode.'_'.($suffix++);
                    }

                    DB::table('mm_attribute_definitions')->insert([
                        'category_id' => $categoryId,
                        'code' => Str::limit($codeCandidate, 60, ''),
                        'label' => $label,
                        'data_type' => 'text',
                        'unit_id' => null,
                        'is_required' => false,
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Master categories and specifications are operational data; never remove them on rollback.
    }
};
