<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appliance extends Model
{
    use HasFactory;

    // Estos son los campos que permitimos guardar desde el formulario
    protected $fillable = [
        'property_id',
        'type',
        'brand',
        'model',
        'serial_number',
        'has_warranty',
        'image_path', // ¡Importante para la foto!
    ];

    // Relación inversa: Un electrodoméstico pertenece a una Propiedad
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}