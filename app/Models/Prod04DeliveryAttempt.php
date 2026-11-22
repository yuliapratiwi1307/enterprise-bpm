<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod04DeliveryAttempt extends Model
{
    protected $table = 'prod04_delivery_attempts';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Prod04DistributionRecipient::class, 'recipient_id');
    }
}
