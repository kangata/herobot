<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarlyAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_name',
        'email',
        'website',
        'organization_type',
        'description',
    ];
}
