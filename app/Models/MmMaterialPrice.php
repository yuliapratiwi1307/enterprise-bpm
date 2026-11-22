<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmMaterialPrice extends Model
{
    protected $table = 'mm_material_prices';

    protected $fillable = [
        'material_variant_id',
        'supplier_id',
        'price_unit_id',
        'source_quote_id',
        'price_type',
        'amount',
        'conversion_factor',
        'currency',
        'effective_from',
        'effective_until',
        'source_name',
        'change_reason',
        'recorded_by',
    ];

    protected $casts = [
        'material_variant_id' => 'integer',
        'supplier_id' => 'integer',
        'price_unit_id' => 'integer',
        'source_quote_id' => 'integer',
        'amount' => 'decimal:4',
        'conversion_factor' => 'decimal:8',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Normalized price per one base unit, using the conversion factor
     * snapshotted on this historical price row.
     */
    public function getAmountInBaseUnitAttribute(): ?string
    {
        if ($this->amount === null || $this->conversion_factor === null) {
            return null;
        }

        $factor = (float) $this->conversion_factor;
        if ($factor <= 0) {
            return null;
        }

        return number_format((float) $this->amount / $factor, 8, '.', '');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MmMaterialVariant::class, 'material_variant_id');
    }

    public function priceUnit(): BelongsTo
    {
        return $this->belongsTo(MmUnit::class, 'price_unit_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(MmSupplier::class, 'supplier_id');
    }

    public function sourceQuote(): BelongsTo
    {
        return $this->belongsTo(MmSupplierQuote::class, 'source_quote_id');
    }
}
