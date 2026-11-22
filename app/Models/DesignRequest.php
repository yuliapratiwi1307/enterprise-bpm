<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignRequest extends Model
{
    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'deadline_reminder_sent_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
            'cancellation_acknowledged_at' => 'datetime',
            'cancellation_reminder_sent_at' => 'datetime',
        ];
    }

    public function marketingBrief(): BelongsTo
    {
        return $this->belongsTo(MarketingBrief::class);
    }

    public function currentPic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_pic_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DesignRequestAssignment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DesignRequestAttachment::class)->orderByDesc('version');
    }

    public function deadlineChanges(): HasMany
    {
        return $this->hasMany(DesignRequestDeadlineChange::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProductionWorkflowEvent::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline_at !== null
            && $this->deadline_at->isPast()
            && ! in_array($this->status, ['COMPLETED', 'CANCELLED'], true);
    }
}
