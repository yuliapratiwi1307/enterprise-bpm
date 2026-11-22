<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmUnit extends Model
{
    protected $table = 'mm_units';

    protected $fillable = ['code', 'name', 'dimension', 'usage_group', 'is_specification_unit', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'is_specification_unit' => 'boolean'];


    public function supportsSpecification(): bool
    {
        return (bool) $this->is_specification_unit;
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MmMaterialVariant::class, 'base_unit_id');
    }

    public function attributeDefinitions(): HasMany
    {
        return $this->hasMany(MmAttributeDefinition::class, 'unit_id');
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(MmSupplierQuote::class, 'price_unit_id');
    }

    public function materialPrices(): HasMany
    {
        return $this->hasMany(MmMaterialPrice::class, 'price_unit_id');
    }
}
