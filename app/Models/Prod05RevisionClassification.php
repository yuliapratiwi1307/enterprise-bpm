<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod05RevisionClassification extends Model
{
    protected $table = 'prod05_revision_classifications';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function validation(): BelongsTo
    {
        return $this->belongsTo(Prod05Validation::class, 'validation_id');
    }

    public function recommendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
