<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod05ActivityControl extends Model
{
    protected $table = 'prod05_activity_controls';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'downstream_dependencies' => 'array',
            'hold_requested_at' => 'datetime',
            'hold_released_at' => 'datetime',
        ];
    }

    public function validation(): BelongsTo
    {
        return $this->belongsTo(Prod05Validation::class, 'validation_id');
    }

    public function affectedByValidation(): BelongsTo
    {
        return $this->belongsTo(Prod05Validation::class, 'affected_by_validation_id');
    }
}
