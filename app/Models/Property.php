<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <--- ESTA LINEA ES VITAL
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    // Si estás usando nombres en español en la BD, descomenta esto:
    // protected $table = 'propiedades';

    protected $fillable = [
        'client_id',
        'type',
        'custom_curp',
        'address',
        'coordinates'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}