<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'concept' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}