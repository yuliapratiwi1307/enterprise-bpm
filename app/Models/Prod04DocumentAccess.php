<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prod04DocumentAccess extends Model
{
    protected $table = 'prod04_document_access';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'viewed_at' => 'datetime',
        ];
    }

    public function briefVersion(): BelongsTo
    {
        return $this->belongsTo(Prod04BriefVersion::class, 'brief_version_id');
    }
}
