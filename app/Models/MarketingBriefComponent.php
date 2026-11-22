<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingBriefComponent extends Model
{
    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return ['is_provisional' => 'boolean'];
    }

    public function marketingBrief(): BelongsTo
    {
        return $this->belongsTo(MarketingBrief::class);
    }
}
