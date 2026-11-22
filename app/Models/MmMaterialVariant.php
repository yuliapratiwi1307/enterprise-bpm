<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmMaterialVariant extends Model
{
    protected $table = 'mm_material_variants';

    protected $fillable = ['material_id', 'sku', 'variant_name', 'base_unit_id', 'specification_note', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['material_id' => 'integer', 'base_unit_id' => 'integer', 'is_active' => 'boolean'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MmMaterial::class, 'material_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(MmUnit::class, 'base_unit_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(MmVariantAttribute::class, 'variant_id');
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(MmSupplierQuote::class, 'material_variant_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MmMaterialPrice::class, 'material_variant_id');
    }
}
