<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmSupplier extends Model
{
    protected $table = 'mm_suppliers';

    protected $fillable = ['code', 'name', 'contact_name', 'phone', 'email', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function quotes(): HasMany
    {
        return $this->hasMany(MmSupplierQuote::class, 'supplier_id');
    }
}
