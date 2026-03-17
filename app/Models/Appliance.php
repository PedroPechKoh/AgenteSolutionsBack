<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appliance extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'type',
        'brand',
        'model',
        'serial_number',
        'has_warranty',
        'image_path', 
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}