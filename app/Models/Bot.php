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

    protected $fillable = ['name', 'description', 'personality'];

    protected $appends = ['integrations'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->team_id = Auth::user()->currentTeam->id;
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function getIntegrationsAttribute()
    {
        return [];
    }
}
