<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'amount'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
