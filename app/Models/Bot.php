<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\HasTeams;

class Bot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['team_id', 'name', 'description'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function integrations()
    {
        return $this->morphedByMany(Integration::class, 'connectable', 'bot_connections');
    }

    public function knowledge()
    {
        return $this->morphedByMany(Knowledge::class, 'connectable', 'bot_connections');
    }
}
