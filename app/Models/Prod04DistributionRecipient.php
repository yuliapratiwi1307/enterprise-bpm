<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prod04DistributionRecipient extends Model
{
    protected $table = 'prod04_distribution_recipients';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function briefVersion(): BelongsTo
    {
        return $this->belongsTo(Prod04BriefVersion::class, 'brief_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Prod04DeliveryAttempt::class, 'recipient_id');
    }
}
