<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientBrief extends Model
{
    protected $fillable = [
        'client_name', 'communication_source', 'requirement_type', 'description',
        'client_pic', 'target_date', 'work_location',
        'project_name', 'project_type', 'brand', 'estimated_value', 'priority',
        'project_objectives', 'project_description',
        'client_type', 'client_address', 'client_position', 'client_email',
        'client_phone', 'client_notes',
        'key_requirements', 'target_audience', 'reference_inspiration',
    ];

    protected $casts = [
        'target_date' => 'date',
        'recorded_at' => 'datetime',
        'stage_3_started_at' => 'datetime',
        'estimated_value' => 'decimal:2',
    ];

    public function marketing()
    {
        return $this->belongsTo(User::class, 'marketing_user_id');
    }

    public function events()
    {
        return $this->hasMany(ClientBriefEvent::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClientBriefAttachment::class);
    }

    public function primaryBrief(): BelongsTo
    {
        return $this->belongsTo(self::class, 'primary_brief_id');
    }

    public function relatedBriefs(): HasMany
    {
        return $this->hasMany(self::class, 'primary_brief_id');
    }

    public function getIsCompleteAttribute(): bool
    {
        foreach (['client_name', 'communication_source', 'requirement_type', 'description'] as $field) {
            if (blank($this->{$field})) {
                return false;
            }
        }

        return true;
    }
}
