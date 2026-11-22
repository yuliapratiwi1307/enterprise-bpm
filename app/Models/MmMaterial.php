<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmMaterial extends Model
{
    protected $table = 'mm_materials';

    protected $fillable = ['category_id', 'code', 'name', 'description', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['category_id' => 'integer', 'is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MmCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MmMaterialVariant::class, 'material_id');
    }
}
