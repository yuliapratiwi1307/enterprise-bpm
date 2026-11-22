<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmSupplierQuote extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'mm_supplier_quotes';

    protected $fillable = ['material_variant_id', 'supplier_id', 'price_unit_id', 'amount', 'currency', 'quoted_on', 'valid_until', 'supplier_reference', 'source_document_path', 'notes', 'recorded_by'];

    protected $attributes = ['approval_status' => self::STATUS_PENDING];

    protected $casts = [
        'material_variant_id' => 'integer',
        'supplier_id' => 'integer',
        'price_unit_id' => 'integer',
        'amount' => 'decimal:4',
        'quoted_on' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MmMaterialVariant::class, 'material_variant_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(MmSupplier::class, 'supplier_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function priceUnit(): BelongsTo
    {
        return $this->belongsTo(MmUnit::class, 'price_unit_id');
    }

    public function materialPrices(): HasMany
    {
        return $this->hasMany(MmMaterialPrice::class, 'source_quote_id');
    }
}
