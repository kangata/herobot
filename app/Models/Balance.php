<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'amount'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'team_id', 'team_id');
    }

    /**
     * Convert integer amount to decimal for display
     */
    public function getDecimalAmountAttribute()
    {
        return $this->amount / 1000000;
    }

    /**
     * Set amount from decimal value (converts to integer for storage)
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round($value * 1000000);
    }

}
