<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'chat_history_id',
        'status',
        'input_parameters',
        'output',
        'error',
    ];

    protected $casts = [
        'input_parameters' => 'array',
        'output' => 'array',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function chatHistory()
    {
        return $this->belongsTo(ChatHistory::class);
    }
}
