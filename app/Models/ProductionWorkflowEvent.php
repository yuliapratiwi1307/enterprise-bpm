<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionWorkflowEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function marketingBrief(): BelongsTo
    {
        return $this->belongsTo(MarketingBrief::class);
    }

    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
