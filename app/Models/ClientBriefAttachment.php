<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientBriefAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_brief_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'category',
        'notes',
    ];

    protected $hidden = ['storage_path'];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function clientBrief(): BelongsTo
    {
        return $this->belongsTo(ClientBrief::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
