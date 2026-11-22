<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod04Acknowledgement extends Model
{
    protected $table = 'prod04_acknowledgements';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }

    public function briefVersion(): BelongsTo
    {
        return $this->belongsTo(Prod04BriefVersion::class, 'brief_version_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
