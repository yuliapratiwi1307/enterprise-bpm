<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prod05Validation extends Model
{
    protected $table = 'prod05_validations';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'provisional_data' => 'array',
            'on_hold_scope' => 'array',
            'manager_decision_at' => 'datetime',
            'hold_released_at' => 'datetime',
        ];
    }

    public function briefVersion(): BelongsTo
    {
        return $this->belongsTo(Prod04BriefVersion::class, 'brief_version_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function holdReleasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hold_released_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Prod05ValidationEvent::class, 'validation_id');
    }

    public function revisionClassifications(): HasMany
    {
        return $this->hasMany(Prod05RevisionClassification::class, 'validation_id');
    }

    public function activityControls(): HasMany
    {
        return $this->hasMany(Prod05ActivityControl::class, 'validation_id');
    }

    public function marketingBrief()
    {
        return $this->briefVersion()->marketingBrief();
    }
}
