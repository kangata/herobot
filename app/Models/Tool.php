<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'type',
        'params',
        'parameters_schema',
        'is_active',
    ];

    protected $casts = [
        'params' => 'array',
        'parameters_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function executions()
    {
        return $this->hasMany(ToolExecution::class);
    }

    public function bots()
    {
        return $this->morphToMany(Bot::class, 'connectable', 'bot_connections');
    }
}
