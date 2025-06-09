<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'sender',
        'message',
        'response',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
