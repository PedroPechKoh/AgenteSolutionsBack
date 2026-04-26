<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    protected $fillable = ['name', 'content'];

    protected $casts = [
        'content' => 'array',
    ];
}
