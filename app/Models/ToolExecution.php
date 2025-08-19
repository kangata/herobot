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
        'duration',
        'executed_at',
    ];

    protected $casts = [
        'input_parameters' => 'array',
        'output' => 'array',
        'executed_at' => 'datetime',
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
