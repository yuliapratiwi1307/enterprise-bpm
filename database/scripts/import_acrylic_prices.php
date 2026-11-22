<?php

use App\Models\MmMaterial;
use App\Models\MmSpecificationTemplate;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use App\Services\MmMasterDataService;
use App\Services\MmVariantService;
use App\Services\MmMaterialPriceService;
use Illuminate\Support\Facades\DB;

// TRUE = pemeriksaan saja
// FALSE = benar-benar menyimpan data
$DRY_RUN = false;

$categoryId = 5;
$templateId = 5;
$unitId = 18;
$supplierId = 6;
$effectiveDate = '2026-11-16';

$rows = [
    // BENING 122 x 244
    ['Bening', 1.8, 122, 244, 325000],
    ['Bening', 2,   122, 244, 350000],
    ['Bening', 2.8, 122, 244, 500000],
    ['Bening', 3,   122, 244, 535000],
    ['Bening', 4,   122, 244, 710000],
    ['Bening', 5,   122, 244, 890000],
    ['Bening', 6,   122, 244, 1056000],
    ['Bening', 8,   122, 244, 1400000],
    ['Bening', 10,  122, 244, 1750000],
    ['Bening', 12,  122, 244, 2280000],
    ['Bening', 15,  122, 244, 2960000],
    ['Bening', 20,  122, 244, 4100000],

    // BENING 100 x 200
    ['Bening', 2, 100, 200, 285000],
    ['Bening', 3, 100, 200, 395000],
    ['Bening', 5, 100, 200, 670000],
    ['Bening', 6, 100, 200, 810000],
    ['Bening', 8, 100, 200, 1075000],
    ['Bening', 10, 100, 200, 1330000],
    ['Bening', 12, 100, 200, 1600000],

    // PUTIH SUSU 122 x 244
    ['Putih Susu', 2, 122, 244, 440000],
    ['Putih Susu', 3, 122, 244, 630000],
    ['Putih Susu', 5, 122, 244, 1040000],

    // PUTIH SUSU 100 x 200
    ['Putih Susu', 2, 100, 200, 305000],
    ['Putih Susu', 3, 100, 200, 430000],
    ['Putih Susu', 5, 100, 200, 715000],

    // PUTIH SOLID 122 x 244
    ['Putih Solid', 2, 122, 244, 460000],
    ['Putih Solid', 3, 122, 244, 650000],
    ['Putih Solid', 5, 122, 244, 1050000],

    // PUTIH SOLID 100 x 200
    ['Putih Solid', 2, 100, 200, 315000],
    ['Putih Solid', 3, 100, 200, 440000],
    ['Putih Solid', 5, 100, 200, 735000],

    // WARNA 122 x 244
    ['Warna', 2, 122, 244, 490000],
    ['Warna', 3, 122, 244, 700000],
    ['Warna', 5, 122, 244, 1150000],

    // WARNA 100 x 200
    ['Warna', 2, 100, 200, 340000],
    ['Warna', 3, 100, 200, 475000],

    // BENING 200 x 300
    ['Bening', 3, 200, 300, 1300000],
    ['Bening', 4, 200, 300, 1800000],
    ['Bening', 5, 200, 300, 2200000],

    // PUTIH SUSU 200 x 300
    ['Putih Susu', 3, 200, 300, 1400000],
    ['Putih Susu', 4, 200, 300, 1900000],
    ['Putih Susu', 5, 200, 300, 2300000],

    // MIRROR 122 x 244, ketebalan 2 mm
    ['Silver',   2, 122, 244, 700000],
    ['Gold',     2, 122, 244, 700000],
    ['Rose Gold',2, 122, 244, 700000],
    ['Bronze',   2, 122, 244, 700000],
    ['Blue',     2, 122, 244, 700000],
    ['Black',    2, 122, 244, 700000],
    ['Red',      2, 122, 244, 700000],
];

$keyOf = function ($color, $thickness, $length, $width) {
    return implode('|', [
        mb_strtolower(trim($color)),
        rtrim(rtrim((string) $thickness, '0'), '.'),
        (string) $length,
        (string) $width,
    ]);
};

// Validasi master data
$template = MmSpecificationTemplate::query()
    ->whereKey($templateId)
    ->where('category_id', $categoryId)
    ->where('is_active', true)
    ->firstOrFail();

$supplier = MmSupplier::query()
    ->whereKey($supplierId)
    ->where('is_active', true)
    ->firstOrFail();

if ($supplier->name !== 'CV SENTOSA AKRILIK') {
    throw new RuntimeException('Supplier ID tidak cocok.');
}

$unit = MmUnit::query()
    ->whereKey($unitId)
    ->where('is_active', true)
    ->firstOrFail();

if ($unit->name !== 'Lembar') {
    throw new RuntimeException('Satuan ID tidak cocok.');
}

// Audit kombinasi yang sudah ada
$existingKeys = [];

$materials = MmMaterial::query()
    ->where('category_id', $categoryId)
    ->with(['variants.attributes'])
    ->get();

foreach ($materials as $material) {
    foreach ($material->variants as $variant) {
        $attributes = [];

        foreach ($variant->attributes as $attribute) {
            $attributes[(int) $attribute->attribute_definition_id] =
                $attribute->value_text
                ?? $attribute->value_number
                ?? null;
        }

        if (
            isset($attributes[6], $attributes[7],
                  $attributes[8], $attributes[10])
        ) {
            $existingKeys[$keyOf(
                $attributes[10],
                $attributes[6],
                $attributes[7],
                $attributes[8]
            )] = true;
        }
    }
}

$pending = [];

foreach ($rows as $row) {
    [$color, $thickness, $length, $width, $price] = $row;

    $key = $keyOf($color, $thickness, $length, $width);

    if (isset($existingKeys[$key])) {
        continue;
    }

    $pending[] = $row;
}

echo PHP_EOL . "===== AUDIT ACRYLIC =====" . PHP_EOL;
echo "Daftar sumber       : " . count($rows) . PHP_EOL;
echo "Sudah tersedia      : " . (count($rows) - count($pending)) . PHP_EOL;
echo "Belum tersedia      : " . count($pending) . PHP_EOL;

foreach ($pending as $row) {
    echo implode(' | ', $row) . PHP_EOL;
}

if ($DRY_RUN) {
    echo PHP_EOL . "MODE PEMERIKSAAN SAJA." . PHP_EOL;
    echo "Belum ada perubahan database." . PHP_EOL;
    echo "Jika hasil sudah benar, ubah DRY_RUN menjadi false." . PHP_EOL;
    return;
}

// Ambil actor dari histori harga sebelumnya
$actorId = DB::table('mm_material_prices')
    ->whereNotNull('recorded_by')
    ->orderByDesc('id')
    ->value('recorded_by');

if (!$actorId || !DB::table('users')->where('id', $actorId)->exists()) {
    throw new RuntimeException('Actor tidak ditemukan. Impor dibatalkan.');
}

$master = app(MmMasterDataService::class);
$variants = app(MmVariantService::class);
$prices = app(MmMaterialPriceService::class);

DB::transaction(function () use (
    $pending, $categoryId, $template, $unitId,
    $supplierId, $effectiveDate, $actorId,
    $master, $variants, $prices
) {
    DB::statement('LOCK TABLE mm_materials IN EXCLUSIVE MODE');

    $maxMaterial = 0;
    foreach (DB::table('mm_materials')->pluck('code') as $code) {
        if (preg_match('/^MAT-(\d+)$/', $code, $match)) {
            $maxMaterial = max($maxMaterial, (int) $match[1]);
        }
    }

    $maxSku = 0;
    foreach (DB::table('mm_material_variants')->pluck('sku') as $sku) {
        if (preg_match('/^SYS-MAT-(\d+)$/', $sku, $match)) {
            $maxSku = max($maxSku, (int) $match[1]);
        }
    }

    $inserted = 0;

    foreach ($pending as $row) {
        [$color, $thickness, $length, $width, $price] = $row;

        $maxMaterial++;
        $maxSku++;

        $code = 'MAT-' . str_pad($maxMaterial, 6, '0', STR_PAD_LEFT);
        $sku = 'SYS-MAT-' . str_pad($maxSku, 6, '0', STR_PAD_LEFT);

        $material = $master->createMaterial([
            'category_id' => $categoryId,
            'specification_template_id' => $template->id,
            'code' => $code,
            'name' => 'Acrylic',
            'description' => null,
        ], (int) $actorId);

        $variant = $variants->createVariant([
            'material_id' => $material->id,
            'sku' => $sku,
            'variant_name' => $color . ' - ' .
                $length . ' x ' . $width . ' - ' .
                $thickness . ' mm',
            'base_unit_id' => $unitId,
            'specification_note' => null,
        ], (int) $actorId);

        $variants->setAttributes($variant, [
            [
                'attribute_definition_id' => 6,
                'value_text' => (string) $thickness,
            ],
            [
                'attribute_definition_id' => 7,
                'value_text' => (string) $length,
            ],
            [
                'attribute_definition_id' => 8,
                'value_text' => (string) $width,
            ],
            [
                'attribute_definition_id' => 10,
                'value_text' => $color,
            ],
        ], $template, false);

        $prices->updateSupplierPrice([
            'supplier_id' => $supplierId,
            'price_unit_id' => $unitId,
            'amount' => $price,
            'currency' => 'IDR',
            'effective_from' => $effectiveDate,
            'change_reason' => 'Daftar harga CV SENTOSA AKRILIK.',
        ], (int) $variant->id, (int) $actorId);

        $inserted++;

        echo "BERHASIL: {$code} | {$color} | {$thickness} mm | Rp" .
            number_format($price, 0, ',', '.') . PHP_EOL;
    }

    echo PHP_EOL . "TOTAL DIMASUKKAN: {$inserted}" . PHP_EOL;
});

echo PHP_EOL . "TRANSAKSI SELESAI." . PHP_EOL;
