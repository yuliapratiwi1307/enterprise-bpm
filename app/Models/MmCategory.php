<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmCategory extends Model
{
    protected $table = 'mm_categories';

    protected $fillable = ['code', 'name', 'parent_id', 'is_active'];

    protected $casts = ['parent_id' => 'integer', 'is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MmCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MmCategory::class, 'parent_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MmMaterial::class, 'category_id');
    }

    public function attributeDefinitions(): HasMany
    {
        return $this->hasMany(MmAttributeDefinition::class, 'category_id');
    }
}
