<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'phone',
        'connected',
        'whatsapp_id',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
