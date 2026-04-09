<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 

class PropertyController extends Controller
{
    // ---------------------------------------------------
    // 1. GUARDAR PROPIEDAD
    // ---------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'estado' => 'required|string',
            'municipio' => 'required|string',
            'colonia' => 'required|string',
            'calle' => 'required|string',
            'numero' => 'required|string',
            'property_name' => 'nullable|string|max:191', 
            'facade_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
        ]);

        $user = $request->user();
        $clientId = null;

        if ($user->role_id == 3) {
            $cliente = DB::table('clients')->where('user_id', $user->id)->first();
            if (!$cliente) {
                return response()->json(['error' => 'No se encontró el perfil de cliente asociado a este usuario.'], 404);
            }
            $clientId = $cliente->id;
        } else {
            $clientId = $request->client_id; 
        }

        // Lógica de CURP Personalizado (se mantiene igual)
        $tipo = strtoupper(substr($request->type, 0, 2));
        $estado_limpio = Str::ascii($request->estado);
        $estado_curp = strtoupper(substr($estado_limpio, 0, 3));
        $muni_limpio = Str::ascii($request->municipio);
        $municipio_curp = strtoupper(substr($muni_limpio, 0, 3));
        $colonia = strtoupper(substr($request->colonia, 0, 3));
        $calle_curp = strtoupper(str_replace(' ', '', $request->calle));
        $numero_curp = strtoupper(str_replace(' ', '', $request->numero));
        $random = strtoupper(Str::random(3));
        $custom_curp = "{$tipo}-{$estado_curp}-{$municipio_curp}-{$colonia}-{$calle_curp}-{$numero_curp}-{$random}";

        $direccion_completa = "Calle {$request->calle} #{$request->numero}";
        if ($request->cruzamientos) {
            $direccion_completa .= " x {$request->cruzamientos}";
        }
        $direccion_completa .= ", Col. {$request->colonia}, {$request->municipio}, {$request->estado}";

        $path = null;
        if ($request->hasFile('facade_photo')) {
            $path = $request->file('facade_photo')->store('properties/facades', 'public');
        }

        $property = new Property();
        $property->client_id = $clientId;
        $property->type = $request->type;
        $property->state = $request->estado;
        $property->address = $direccion_completa;
        $property->coordinates = $request->coordinates;
        $property->custom_curp = $custom_curp;
        
        // 👇 ASIGNACIÓN DE CAMPOS NUEVOS 👇
        $property->property_name = $request->property_name;
        $property->facade_photo_path = $path;
        
        $property->save();

        return response()->json([
            'message' => 'Propiedad guardada con éxito',
            'property' => $property
        ], 201);
    }

    // ---------------------------------------------------
    // 2. OBTENER PROPIEDADES (Para la tabla de React)
    // ---------------------------------------------------
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Property::with('client')->orderByDesc('created_at');

            if ($user->role_id == 3) {
                $cliente = DB::table('clients')->where('user_id', $user->id)->first();
                if ($cliente) {
                    $query->where('client_id', $cliente->id); 
                } else {
                    return response()->json([], 200); 
                }
            }

            $propiedades = $query->get();

           $formateadas = $propiedades->map(function ($p) {
            
            $tienePendiente = $p->services()
                ->whereNotIn('status', ['Finalizado', 'Cancelado'])
                ->exists();

            return [
                'id' => $p->id,
                'client_id' => $p->client_id, 
                'propietario' => $p->client ? $p->client->name : 'Sin Propietario',
                'nombre_propiedad' => $p->property_name ?? 'Propiedad sin nombre',
                'direccion' => $p->address,
                'fecha' => $p->created_at ? $p->created_at->format('Y-m-d') : 'Sin fecha',
                'tipo' => strtoupper($p->type),
                'curp' => $p->custom_curp,
                'coordenadas' => $p->coordinates,
                'foto_url' => $p->facade_photo_path ? asset('storage/' . $p->facade_photo_path) : null,
                'created_at' => $p->created_at,
                'has_pending_service' => $tienePendiente 
            ];
        });

            return response()->json($formateadas, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar propiedades: ' . $e->getMessage()], 500);
        }
    }
}