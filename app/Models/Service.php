<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'requested_by',
        'assigned_to', 
        'service_category_id',
        'service_type',
        'priority',
        'status',
        'title',
        'description',
        'scheduled_start',
        'scheduled_end',
        'real_start',
        'real_end'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}

