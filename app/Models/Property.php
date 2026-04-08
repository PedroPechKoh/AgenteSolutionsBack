<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 
        'type', 
        'state', 
        'custom_curp', 
        'address', 
        'coordinates',
        'property_name',       // 👈 Campo nuevo para el nombre
        'facade_photo_path'    // 👈 Campo nuevo para la foto
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'property_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    
    public function areas()
    {
        return $this->hasMany(PropertyArea::class);
    }
}