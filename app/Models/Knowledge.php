<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Knowledge extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'qa',
        'text',
        'filepath',
        'filename',
        'size',
    ];

    protected $casts = [
        'qa' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function bots()
    {
        return $this->morphToMany(Bot::class, 'connectable', 'bot_connections');
    }
}
