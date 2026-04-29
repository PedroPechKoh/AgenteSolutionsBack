<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// Importamos la API pura de Cloudinary (La Opción Nuclear)
use Cloudinary\Cloudinary;

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
            'facade_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240', // Límite de 10MB
        ]);

        // FORZAMOS LA LECTURA DESDE SANCTUM (El Gafete)
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['error' => 'No autorizado. Token inválido o ausente.'], 401);
        }

        $clientId = null;

        if ($user->role_id == 3) {

            // 🔥 AUTO-REPARADOR NIVEL DIOS 🔥
            // Buscamos por ID o por Correo para que no haya duplicados
            $cliente = DB::table('clients')
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            if (!$cliente) {
                // 1. Si de verdad no existe, lo creamos
                $clientId = DB::table('clients')->insertGetId([
                    'user_id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name) ?: 'Cliente Web',
                    'email' => $user->email,
                    'phone' => $user->phone_number ?? 'Sin teléfono',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                // 2. Si ya existía el correo pero estaba desvinculado de este nuevo ID, lo reconectamos silenciosamente
                if ($cliente->user_id !== $user->id) {
                    DB::table('clients')->where('id', $cliente->id)->update([
                        'user_id' => $user->id
                    ]);
                }
                $clientId = $cliente->id;
            }

        } else {
            $clientId = $request->client_id;
        }

        // --- SUBIDA A CLOUDINARY (Fachadas) ---
        $uploadedFileUrl = null;
        if ($request->hasFile('facade_photo')) {
            $cloudinary = new Cloudinary('cloudinary://942191234587844:VmNYB6w4vj3DdLqI9SZSKVofOi0@dcj5rcpi8');
            $respuestaNube = $cloudinary->uploadApi()->upload($request->file('facade_photo')->getRealPath(), [
                'folder' => 'agente_propiedades' // Guardamos las casas en su propia carpeta en la nube
            ]);
            $uploadedFileUrl = $respuestaNube['secure_url'];
        }

        // Lógica de CURP Personalizado
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

        $property = new Property();
        $property->client_id = $clientId;
        $property->type = $request->type;
        $property->state = $request->estado;
        $property->address = $direccion_completa;
        $property->coordinates = $request->coordinates;
        $property->custom_curp = $custom_curp;
        $property->property_name = $request->property_name;

        // GUARDAMOS LA URL DIRECTA DE LA NUBE (O NULL SI NO SUBIERON NADA)
        $property->facade_photo_path = $uploadedFileUrl;

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
            // FORZAMOS LA LECTURA DESDE SANCTUM
            $user = auth('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'error' => 'No autorizado. El token es inválido o no se recibió correctamente.'
                ], 401);
            }

            $query = Property::with('client')->orderByDesc('created_at');

            // Filtrado basado en el rol (Si es Cliente 3, solo ve las suyas)
            if ($user->role_id == 3) {
                $cliente = DB::table('clients')->where('user_id', $user->id)->first();
                if ($cliente) {
                    $query->where('client_id', $cliente->id);
                } else {
                    // Si el usuario no tiene perfil, le devolvemos una lista vacía
                    return response()->json([], 200);
                }
            }

            $propiedades = $query->get();

            $formateadas = $propiedades->map(function ($p) {
                $tienePendiente = DB::table('services')
                    ->where('property_id', $p->id)
                    ->whereNotIn('status', ['Finalizado', 'Cancelado'])
                    ->exists();

                $levantamiento = DB::table('services')
                    ->where('property_id', $p->id)
                    ->where('title', 'Levantamiento Inicial')
                    ->first();

                return [
                    'id' => $p->id,
                    'client_id' => $p->client_id,
                    'client_email' => $p->client ? $p->client->email : null,
                    'propietario' => $p->client ? $p->client->name : 'Sin Propietario',
                    'nombre_propiedad' => $p->property_name ?? 'Propiedad sin nombre',
                    'direccion' => $p->address,
                    'fecha' => $p->created_at ? $p->created_at->format('Y-m-d') : 'Sin fecha',
                    'tipo' => strtoupper($p->type),
                    'curp' => $p->custom_curp,
                    'coordenadas' => $p->coordinates,
                    'foto_url' => $p->facade_photo_path,
                    'created_at' => $p->created_at,
                    'has_pending_service' => $tienePendiente,
                    'id_levantamiento' => $levantamiento ? $levantamiento->id : null,
                    'levantamiento_realizado' => $levantamiento ? true : false,
                ];
            });

            return response()->json($formateadas, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar propiedades: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------
    // 3. ELIMINAR PROPIEDAD
    // ---------------------------------------------------
    public function destroy($id)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['error' => 'No autorizado.'], 401);
            }

            $property = Property::findOrFail($id);

            // Nota: Esto elimina fotos solo si quedaron algunas viejas guardadas en local.
            // Las de Cloudinary se quedan en la nube como respaldo.
            if ($property->facade_photo_path && !str_contains($property->facade_photo_path, 'cloudinary.com')) {
                Storage::disk('public')->delete($property->facade_photo_path);
            }

            $property->delete();

            return response()->json(['message' => 'Propiedad eliminada con éxito'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la propiedad: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------
    // 8. ACTUALIZAR PROPIEDAD (Nombre y Foto)
    // ---------------------------------------------------
    public function updateProperty(Request $request, $id)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) return response()->json(['error' => 'No autorizado.'], 401);

            $property = Property::findOrFail($id);

            // Validación
            $request->validate([
                'property_name' => 'nullable|string|max:191',
                'facade_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            ]);

            // 1. Actualizar Nombre
            if ($request->has('property_name')) {
                $property->property_name = $request->property_name;
            }

            // 2. Actualizar Foto
            if ($request->hasFile('facade_photo')) {
                $cloudinary = new Cloudinary('cloudinary://942191234587844:VmNYB6w4vj3DdLqI9SZSKVofOi0@dcj5rcpi8');
                $respuestaNube = $cloudinary->uploadApi()->upload($request->file('facade_photo')->getRealPath(), [
                    'folder' => 'agente_propiedades'
                ]);
                $property->facade_photo_path = $respuestaNube['secure_url'];
            }

            $property->save();

            return response()->json([
                'message' => 'Propiedad actualizada con éxito',
                'property' => $property,
                'foto_url' => $property->facade_photo_path 
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------
    // 4. DATOS DEL DASHBOARD DE LA PROPIEDAD
    // ---------------------------------------------------
    public function getDashboardData($id)
    {
        try {
            $propiedad = DB::table('properties')->where('id', $id)->first();
            if (!$propiedad)
                return response()->json(['error' => 'No encontrada'], 404);

            $stats = DB::table('work_orders')
                ->where('property_id', $id)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status');

            $sosCount = DB::table('work_orders')
                ->where('property_id', $id)
                ->where('status', '!=', 'Listo')
                ->where('priority', 'Urgente')
                ->count();

            $historial = DB::table('work_orders')
                ->leftJoin('users', 'work_orders.tecnico_id', '=', 'users.id')
                ->where('work_orders.property_id', $id)
                ->where('work_orders.status', 'Listo')
                ->select('work_orders.*', DB::raw("CONCAT(users.first_name, ' ', users.last_name) as tecnico_nombre"))
                ->orderBy('work_orders.updated_at', 'desc')
                ->limit(5)
                ->get();

            $cotizacionesCount = 0;
            $totalTareas = $sosCount + ($stats['Por Hacer'] ?? 0) + ($stats['En Proceso'] ?? 0) + ($stats['Listo'] ?? 0);
            $avanceObra = $totalTareas > 0 ? round((($stats['Listo'] ?? 0) / $totalTareas) * 100) : 0;

            return response()->json([
                'propiedad' => $propiedad,
                'stats' => [
                    'sos' => $sosCount,
                    'pendientes' => $stats['Por Hacer'] ?? 0,
                    'proceso' => $stats['En Proceso'] ?? 0,
                    'listos' => $stats['Listo'] ?? 0,
                ],
                'historial' => $historial,
                'cotizaciones_pendientes' => $cotizacionesCount,
                'avance_obra' => $avanceObra
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------
    // 5. GUARDAR ÓRDENES DE TRABAJO
    // ---------------------------------------------------
    public function storeWorkOrder(Request $request)
    {
        try {
            $path = null;
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('work_orders', 'public');
            }

            $id = DB::table('work_orders')->insertGetId([
                'property_id' => $request->property_id,
                'zone' => $request->zona,
                'equipment' => $request->equipo,
                'description' => $request->descripcion,
                'evidence_path' => $path,
                'status' => 'Por Hacer',
                'priority' => 'Normal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'id' => $id], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------
    // 6. OBTENER ÓRDENES DE TRABAJO
    // ---------------------------------------------------
    public function getWorkOrders($id)
    {
        try {
            $orders = DB::table('work_orders')
                ->leftJoin('users', 'work_orders.tecnico_id', '=', 'users.id')
                ->where('work_orders.property_id', $id)
                ->select(
                    'work_orders.*',
                    DB::raw("CONCAT(users.first_name, ' ', users.last_name) as tecnico_nombre")
                )
                ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------
    // 7. ACTUALIZAR ESTADO DE ÓRDENES
    // ---------------------------------------------------
    public function updateWorkOrderStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:Por Hacer,En Proceso,Listo'
            ]);

            DB::table('work_orders')->where('id', $id)->update([
                'status' => $request->status,
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'Estado actualizado']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}