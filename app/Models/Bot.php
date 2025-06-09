<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bot extends Model
{
    use HasFactory, SoftDeletes;

    const DEFAULT_PROMPT = 'You are a helpful AI assistant. You aim to provide accurate, helpful, and concise responses while being friendly and professional.';

    protected $fillable = ['team_id', 'name', 'description', 'prompt'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function channels()
    {
        return $this->morphedByMany(Channel::class, 'connectable', 'bot_connections');
    }

    public function knowledge()
    {
        return $this->morphedByMany(Knowledge::class, 'connectable', 'bot_connections');
    }
}
