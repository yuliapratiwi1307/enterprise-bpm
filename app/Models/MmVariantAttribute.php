<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmVariantAttribute extends Model
{
    protected $table = 'mm_variant_attributes';

    protected $fillable = ['variant_id', 'attribute_definition_id', 'value_text', 'value_number', 'value_boolean'];

    protected $casts = ['variant_id' => 'integer', 'attribute_definition_id' => 'integer', 'value_number' => 'decimal:6', 'value_boolean' => 'boolean'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MmMaterialVariant::class, 'variant_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MmAttributeDefinition::class, 'attribute_definition_id');
    }
}
