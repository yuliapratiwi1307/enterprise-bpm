<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmAttributeDefinition extends Model
{
    protected $table = 'mm_attribute_definitions';

    protected $fillable = ['category_id', 'code', 'label', 'data_type', 'unit_id', 'is_required', 'sort_order', 'is_active'];

    protected $casts = ['category_id' => 'integer', 'unit_id' => 'integer', 'is_required' => 'boolean', 'sort_order' => 'integer', 'is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MmCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MmUnit::class, 'unit_id');
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(MmVariantAttribute::class, 'attribute_definition_id');
    }
}
