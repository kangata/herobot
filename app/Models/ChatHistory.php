<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'bot_id',
        'sender',
        'message',
        'role',
        'media_data',
        'tool_calls',
        'metadata',
        'message_type',
        'raw_content',
        'tool_call_id',
    ];

    protected $casts = [
        'media_data' => 'array',
        'tool_calls' => 'array',
        'metadata' => 'array',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }
}
