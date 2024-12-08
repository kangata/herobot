<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeVector extends Model
{
    protected $fillable = [
        'knowledge_id',
        'text',
        'vector'
    ];

    protected $casts = [
        'vector' => 'array',
    ];

    public function knowledge()
    {
        return $this->belongsTo(Knowledge::class);
    }
} 