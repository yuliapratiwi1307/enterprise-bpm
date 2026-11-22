<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBriefEvent extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'occurred_at' => 'datetime'];
}
