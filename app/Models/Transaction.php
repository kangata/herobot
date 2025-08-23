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
        'transaction_type',
        'description',
        'status',
        'payment_id',
        'payment_method',
        'external_id',
        'payment_details',
        'expired_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_details' => 'array',
        'expired_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Convert integer amount to decimal for display
     */
    public function getDecimalAmountAttribute()
    {
        return $this->amount / 1000000;
    }

    /**
     * Get the formatted amount in credits
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->decimal_amount, 0, ',', '.') . ' credits';
    }

    /**
     * Get the display amount with proper sign based on transaction type
     */
    public function getDisplayAmountAttribute()
    {
        $sign = $this->transaction_type === 'debit' ? '-' : '+';
        return $sign . ' ' . $this->formatted_amount;
    }

    /**
     * Set amount from decimal value (converts to integer for storage)
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round($value * 1000000);
    }

    /**
     * Determine transaction type based on the type field
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_type)) {
                // Set transaction_type based on the type field
                $transaction->transaction_type = in_array($transaction->type, ['usage', 'refund']) ? 'debit' : 'credit';
            }
        });
    }
}
