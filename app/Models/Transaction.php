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
        'payment_details'
    ];

    protected $casts = [
        'payment_details' => 'array'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
