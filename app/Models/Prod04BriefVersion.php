<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prod04BriefVersion extends Model
{
    protected $table = 'prod04_brief_versions';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'submitted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_retrying' => 'boolean',
        ];
    }

    public function marketingBrief(): BelongsTo
    {
        return $this->belongsTo(MarketingBrief::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(Prod04DistributionRecipient::class, 'brief_version_id');
    }

    public function prod05Validation(): HasOne
    {
        return $this->hasOne(Prod05Validation::class, 'brief_version_id');
    }

    public function validationTask(): HasOne
    {
        return $this->hasOne(Prod04ValidationTask::class, 'brief_version_id');
    }

    public function acknowledgement(): HasOne
    {
        return $this->hasOne(Prod04Acknowledgement::class, 'brief_version_id');
    }

    public function slaTracking(): HasOne
    {
        return $this->hasOne(Prod04SlaTracking::class, 'brief_version_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Prod04Event::class, 'brief_version_id');
    }
}
