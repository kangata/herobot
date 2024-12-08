<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'amount',
        'type',
        'description',
        'status',
        'payment_id',
        'payment_method',
        'external_id',
        'payment_details',
        'expired_at',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'expired_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
