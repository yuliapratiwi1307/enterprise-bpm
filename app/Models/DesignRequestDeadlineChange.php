<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRequestDeadlineChange extends Model
{
    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'previous_deadline_at' => 'datetime',
            'requested_deadline_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }
}
