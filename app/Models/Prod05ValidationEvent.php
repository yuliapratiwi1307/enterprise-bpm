<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod05ValidationEvent extends Model
{
    protected $table = 'prod05_validation_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'before_data' => 'array',
            'after_data' => 'array',
        ];
    }

    public function validation(): BelongsTo
    {
        return $this->belongsTo(Prod05Validation::class, 'validation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
