<?php

namespace App\Services;

use App\Models\MmMaterialPrice;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmSupplierQuote;
use App\Models\MmUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmMaterialPriceService
{
    /**
     * Record an immutable reference price from an approved supplier quote.
     * Conversion is snapshotted on the price row so historical prices remain stable.
     */
    public function recordPrice(array $data, ?int $actorId = null): MmMaterialPrice
    {
        MmSafety::check();
        MmSafety::actor($actorId);

        $v = MmSafety::validate($data, [
            'source_quote_id' => ['required', 'integer', Rule::exists('mm_supplier_quotes', 'id')],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'change_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($v, $actorId): MmMaterialPrice {
            $quote = MmSupplierQuote::query()
                ->with('supplier')
                ->whereKey($v['source_quote_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $quote->is_active
                || $quote->approval_status !== MmSupplierQuote::STATUS_APPROVED
                || ! $quote->supplier
                || ! $quote->supplier->is_active) {
                throw ValidationException::withMessages([
                    'source_quote_id' => 'Hanya penawaran aktif yang disetujui dari supplier aktif yang dapat menjadi referensi harga.',
                ]);
            }

            $variant = MmMaterialVariant::query()
                ->whereKey($quote->material_variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            MmSafety::active('mm_material_variants', (int) $variant->id, 'source_quote_id');
            MmSafety::active('mm_materials', (int) $variant->material_id, 'source_quote_id');

            $priceUnit = MmUnit::query()->whereKey($quote->price_unit_id)->lockForUpdate()->firstOrFail();
            MmSafety::active('mm_units', (int) $priceUnit->id, 'source_quote_id');

            $factor = $this->resolveConversionFactor(
                (int) $variant->material_id,
                (int) $variant->base_unit_id,
                (int) $priceUnit->id,
            );

            if ($factor === null) {
                throw ValidationException::withMessages([
                    'source_quote_id' => 'Konversi satuan penawaran belum tersedia di MM-05. Lengkapi konversi material sebelum menetapkan harga referensi.',
                ]);
            }

            $effectiveFrom = $v['effective_from'];
            $effectiveUntil = $v['effective_until'] ?? null;
            $quotedOn = $quote->quoted_on->toDateString();
            $quoteValidUntil = $quote->valid_until?->toDateString();

            if (
                $effectiveFrom < $quotedOn
                || (
                    $quoteValidUntil !== null
                    && (
                        $effectiveFrom > $quoteValidUntil
                        || $effectiveUntil === null
                        || $effectiveUntil > $quoteValidUntil
                    )
                )
            ) {
                throw ValidationException::withMessages([
                    'effective_from' => 'Masa berlaku harga harus berada dalam masa berlaku penawaran supplier.',
                ]);
            }

            return MmMaterialPrice::create([
                'material_variant_id' => $quote->material_variant_id,
                'supplier_id' => $quote->supplier_id,
                'price_unit_id' => $quote->price_unit_id,
                'source_quote_id' => $quote->id,
                'price_type' => 'reference',
                'amount' => $quote->amount,
                'conversion_factor' => $factor,
                'currency' => strtoupper($quote->currency),
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'source_name' => $quote->supplier->name,
                'change_reason' => $v['change_reason'] ?? null,
                'recorded_by' => $actorId,
            ]);
        });
    }

    /**
     * Record a direct supplier price update and retain the previous price row.
     * One price unit represents conversion_factor base units.
     */
    public function updateSupplierPrice(array $data, int $variantId, ?int $actorId = null): MmMaterialPrice
    {
        MmSafety::check();
        MmSafety::actor($actorId);

        $v = MmSafety::validate($data, [
            'supplier_id' => ['required', 'integer', Rule::exists('mm_suppliers', 'id')],
            'price_unit_id' => ['required', 'integer', Rule::exists('mm_units', 'id')],
            'conversion_factor' => ['nullable', 'numeric', 'gt:0', 'max:999999999999'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999999'],
            'currency' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'change_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($v, $variantId, $actorId): MmMaterialPrice {
            $variant = MmMaterialVariant::query()->whereKey($variantId)->lockForUpdate()->firstOrFail();
            MmSafety::active('mm_material_variants', (int) $variant->id, 'variant_id');
            MmSafety::active('mm_materials', (int) $variant->material_id, 'variant_id');

            $supplier = MmSupplier::query()->whereKey($v['supplier_id'])->lockForUpdate()->firstOrFail();
            MmSafety::active('mm_suppliers', (int) $supplier->id, 'supplier_id');

            $unit = MmUnit::query()->whereKey($v['price_unit_id'])->lockForUpdate()->firstOrFail();
            MmSafety::active('mm_units', (int) $unit->id, 'price_unit_id');

            $baseUnitId = (int) $variant->base_unit_id;
            $priceUnitId = (int) $unit->id;

            if ($priceUnitId === $baseUnitId) {
                $factor = 1.0;
            } else {
                if (! in_array($unit->usage_group ?? null, ['price', 'both'], true)) {
                    throw ValidationException::withMessages([
                        'price_unit_id' => 'Satuan yang dipilih bukan satuan harga MM-05.',
                    ]);
                }

                // A price may be recorded before its conversion is known.
                // Keep conversion_factor NULL so downstream HPP cannot invent a normalized cost.
                $factor = isset($v['conversion_factor']) && $v['conversion_factor'] !== ''
                    ? (float) $v['conversion_factor']
                    : null;
            }

            $effectiveFrom = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $v['effective_from'])->startOfDay();

            $previous = MmMaterialPrice::query()
                ->where('material_variant_id', $variant->id)
                ->where('supplier_id', $supplier->id)
                ->where('price_unit_id', $unit->id)
                ->where('price_type', 'reference')
                ->whereNull('effective_until')
                ->orderByDesc('effective_from')
                ->lockForUpdate()
                ->first();

            if ($previous) {
                $previousStart = $previous->effective_from->copy()->startOfDay();
                if ($effectiveFrom->lessThanOrEqualTo($previousStart)) {
                    throw ValidationException::withMessages([
                        'effective_from' => 'Tanggal berlaku harga baru harus setelah tanggal mulai harga sebelumnya.',
                    ]);
                }
                $previous->effective_until = $effectiveFrom->copy()->subDay()->toDateString();
                $previous->save();
            }

            // Keep the current material conversion for future prices, while each
            // historical price row retains its own immutable conversion snapshot.
            if ($priceUnitId !== $baseUnitId && $factor !== null) {
                $conversionKey = [
                    'material_id' => $variant->material_id,
                    'base_unit_id' => $baseUnitId,
                    'price_unit_id' => $priceUnitId,
                ];
                $existingConversion = DB::table('mm_material_unit_conversions')
                    ->where($conversionKey)
                    ->first();

                if ($existingConversion) {
                    DB::table('mm_material_unit_conversions')
                        ->where('id', $existingConversion->id)
                        ->update([
                            'conversion_factor' => $factor,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('mm_material_unit_conversions')->insert($conversionKey + [
                        'conversion_factor' => $factor,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return MmMaterialPrice::create([
                'material_variant_id' => $variant->id,
                'supplier_id' => $supplier->id,
                'price_unit_id' => $unit->id,
                'source_quote_id' => null,
                'price_type' => 'reference',
                'amount' => $v['amount'],
                'conversion_factor' => $factor,
                'currency' => strtoupper($v['currency']),
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_until' => null,
                'source_name' => $supplier->name,
                'change_reason' => $v['change_reason'] ?? null,
                'recorded_by' => $actorId,
            ]);
        });
    }

    private function resolveConversionFactor(int $materialId, int $baseUnitId, int $priceUnitId): ?float
    {
        if ($baseUnitId === $priceUnitId) {
            return 1.0;
        }

        $factor = DB::table('mm_material_unit_conversions')
            ->where('material_id', $materialId)
            ->where('base_unit_id', $baseUnitId)
            ->where('price_unit_id', $priceUnitId)
            ->value('conversion_factor');

        return $factor !== null && (float) $factor > 0 ? (float) $factor : null;
    }
}
