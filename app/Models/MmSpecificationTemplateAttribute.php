<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmSpecificationTemplateAttribute extends Model
{
    protected $table = 'mm_specification_template_attributes';

    protected $fillable = [
        'template_id',
        'attribute_definition_id',
        'is_required',
        'display_order',
        'input_type',
        'unit_id',
        'unit_id_is_explicit',
        'option_values',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'attribute_definition_id' => 'integer',
        'is_required' => 'boolean',
        'display_order' => 'integer',
        'unit_id_is_explicit' => 'boolean',
        'option_values' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MmSpecificationTemplate::class, 'template_id');
    }

    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(MmAttributeDefinition::class, 'attribute_definition_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MmUnit::class, 'unit_id');
    }

    public function hasExplicitUnitPreference(): bool
    {
        if (array_key_exists('unit_id_is_explicit', $this->attributes)) {
            return (bool) $this->unit_id_is_explicit;
        }

        return $this->unit_id !== null;
    }

    public function resolvedUnitId(): ?int
    {
        if ($this->hasExplicitUnitPreference()) {
            return $this->unit_id === null ? null : (int) $this->unit_id;
        }

        $unitId = $this->attributeDefinition?->unit_id;

        return $unitId === null ? null : (int) $unitId;
    }
}
