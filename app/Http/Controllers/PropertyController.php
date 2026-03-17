<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property; 
use Illuminate\Support\Str; 

class PropertyController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'estado' => 'required|string', 
            'municipio' => 'required|string', 
            'colonia' => 'required|string',
            'calle' => 'required|string',
            'numero' => 'required|string',
        ]);

        $tipo = strtoupper(substr($request->type, 0, 2)); 
        $estado_limpio = Str::ascii($request->estado); 
        $estado_curp = strtoupper(substr($estado_limpio, 0, 3)); 
        $muni_limpio = Str::ascii($request->municipio); 
        $municipio_curp = strtoupper(substr($muni_limpio, 0, 3)); 
        $colonia = strtoupper(substr($request->colonia, 0, 3)); 
        $calle = strtoupper(str_replace(' ', '', $request->calle)); 
        $numero = strtoupper(str_replace(' ', '', $request->numero)); 
        $random = strtoupper(Str::random(3)); 
        $custom_curp = "{$tipo}-{$estado_curp}-{$municipio_curp}-{$colonia}-{$calle}-{$numero}-{$random}";
        
        $direccion_completa = "Calle {$request->calle} #{$request->numero}";
        if ($request->cruzamientos) {
            $direccion_completa .= " x {$request->cruzamientos}";
        }
        $direccion_completa .= ", Col. {$request->colonia}, {$request->municipio}, {$request->estado}";

        $property = new Property();
        $property->client_id = $request->client_id;
        $property->type = $request->type;
        $property->state = $request->estado; 
        $property->address = $direccion_completa; 
        $property->coordinates = $request->coordinates;
        $property->custom_curp = $custom_curp; 
        $property->save();

        return response()->json([
            'message' => 'Propiedad guardada con éxito',
            'property' => $property
        ], 201);
    }
}