<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmSpecificationTemplate extends Model
{
    protected $table = 'mm_specification_templates';

    protected $fillable = ['category_id', 'code', 'name', 'description', 'is_active'];

    protected $casts = ['category_id' => 'integer', 'is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MmCategory::class, 'category_id');
    }

    public function templateAttributes(): HasMany
    {
        return $this->hasMany(MmSpecificationTemplateAttribute::class, 'template_id')->orderBy('display_order')->orderBy('id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MmMaterial::class, 'specification_template_id');
    }
}
