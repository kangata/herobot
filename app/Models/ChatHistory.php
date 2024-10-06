<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'sender',
        'message',
        'response',
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }
}