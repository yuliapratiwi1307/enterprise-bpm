<?php

namespace App\Services;

use App\Models\MmMaterialPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class MmReferencePriceSelector
{
    /**
     * @param  array<int, int>  $variantIds
     * @return Collection<int, Collection<string, Collection<int, MmMaterialPrice>>>
     */
    public function latestEligiblePricesForVariants(array $variantIds, string $asOf): Collection
    {
        MmSafety::check();

        if ($variantIds === []) {
            return collect();
        }

        $eligiblePrices = MmMaterialPrice::query()
            ->whereIn('mm_material_prices.material_variant_id', $variantIds)
            ->where('mm_material_prices.price_type', 'reference')
            ->whereDate('mm_material_prices.effective_from', '<=', $asOf)
            ->where(fn (Builder $query) => $query
                ->whereNull('mm_material_prices.effective_until')
                ->orWhereDate('mm_material_prices.effective_until', '>=', $asOf))
            ->whereHas('variant', fn (Builder $variant) => $variant
                ->where('is_active', true)
                ->whereHas('material', fn (Builder $material) => $material
                    ->where('is_active', true)))
            ->where(function (Builder $source) use ($asOf): void {
                $source
                    ->where(function (Builder $direct): void {
                        $direct
                            ->whereNull('mm_material_prices.source_quote_id')
                            ->whereNotNull('mm_material_prices.supplier_id')
                            ->whereHas('supplier', fn (Builder $supplier) => $supplier
                                ->where('is_active', true));
                    })
                    ->orWhereHas('sourceQuote', function (Builder $quote) use ($asOf): void {
                        $quote
                            ->where('mm_supplier_quotes.is_active', true)
                            ->where('mm_supplier_quotes.approval_status', 'APPROVED')
                            ->whereDate('mm_supplier_quotes.quoted_on', '<=', $asOf)
                            ->where(fn (Builder $validity) => $validity
                                ->whereNull('mm_supplier_quotes.valid_until')
                                ->orWhereDate('mm_supplier_quotes.valid_until', '>=', $asOf))
                            ->whereColumn(
                                'mm_supplier_quotes.material_variant_id',
                                'mm_material_prices.material_variant_id',
                            )
                            ->whereColumn(
                                'mm_supplier_quotes.supplier_id',
                                'mm_material_prices.supplier_id',
                            )
                            ->whereColumn(
                                'mm_supplier_quotes.price_unit_id',
                                'mm_material_prices.price_unit_id',
                            )
                            ->whereColumn(
                                'mm_supplier_quotes.currency',
                                'mm_material_prices.currency',
                            )
                            ->whereHas('supplier', fn (Builder $supplier) => $supplier
                                ->where('is_active', true));
                    });
            });

        $latestEffectiveDates = (clone $eligiblePrices)
            ->select([
                'mm_material_prices.material_variant_id',
                'mm_material_prices.currency',
                'mm_material_prices.price_unit_id',
            ])
            ->selectRaw('MAX(mm_material_prices.effective_from) AS latest_effective_from')
            ->groupBy([
                'mm_material_prices.material_variant_id',
                'mm_material_prices.currency',
                'mm_material_prices.price_unit_id',
            ]);

        return (clone $eligiblePrices)
            ->joinSub($latestEffectiveDates, 'latest_effective_dates', function (JoinClause $join): void {
                $join->on(
                    'mm_material_prices.material_variant_id',
                    '=',
                    'latest_effective_dates.material_variant_id',
                )
                    ->on('mm_material_prices.currency', '=', 'latest_effective_dates.currency')
                    ->on('mm_material_prices.price_unit_id', '=', 'latest_effective_dates.price_unit_id')
                    ->on('mm_material_prices.effective_from', '=', 'latest_effective_dates.latest_effective_from');
            })
            ->with(['sourceQuote.supplier', 'priceUnit'])
            ->orderBy('mm_material_prices.material_variant_id')
            ->orderBy('mm_material_prices.currency')
            ->orderBy('mm_material_prices.price_unit_id')
            ->orderBy('mm_material_prices.source_quote_id')
            ->orderBy('mm_material_prices.id')
            ->get()
            ->groupBy('material_variant_id')
            ->map(fn (Collection $prices): Collection => $prices->groupBy(
                fn (MmMaterialPrice $price): string => $price->currency.'|'.$price->price_unit_id,
            ));
    }
}
