<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingBrief extends Model
{
    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'readiness_snapshot' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function clientBrief(): BelongsTo
    {
        return $this->belongsTo(ClientBrief::class);
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_user_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(MarketingBriefComponent::class);
    }

    public function designRequests(): HasMany
    {
        return $this->hasMany(DesignRequest::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProductionWorkflowEvent::class);
    }

    public function prod04Versions(): HasMany
    {
        return $this->hasMany(Prod04BriefVersion::class, 'marketing_brief_id');
    }
}
