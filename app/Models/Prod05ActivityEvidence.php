<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod05ActivityEvidence extends Model
{
    protected $table = 'prod05_activity_evidences';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'brief_revision' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function validation(): BelongsTo
    {
        return $this->belongsTo(Prod05Validation::class, 'validation_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            Prod05ActivityControl::class,
            'activity_control_id'
        );
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
