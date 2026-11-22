<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod04Event extends Model
{
    protected $table = 'prod04_events';

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

    public function briefVersion(): BelongsTo
    {
        return $this->belongsTo(Prod04BriefVersion::class, 'brief_version_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
