<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Notifications\RescheduleRequested;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VisitRescheduled;
use App\Notifications\VisitConfirmed;
use App\Notifications\NewServiceRequested;
use App\Models\PropertyArea;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Log;
use App\Notifications\TechnicianMissedVisitNotification;

class ServiceController extends Controller
{
    // ... (store, index, assignTechnician, assignWorkOrder methods remain unchanged)

    public function store(Request $request)
    {
        try {
            $request->validate([
                'property_id' => 'required|exists:properties,id',
                'title' => 'nullable|string|max:191',
                'property_area_id' => 'nullable|exists:property_areas,id',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
                'evidencia_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
                'evidencia_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            ]);

            $servicio = new Service();
            $servicio->property_id = $request->property_id;
            $servicio->property_area_id = $request->property_area_id;

            $user = $request->user();
            if ($user && $user->role_id == 3) {
                 $servicio->requested_by = $user->id;
            } else {
                 $servicio->requested_by = $request->filled('requested_by') ? $request->requested_by : null;
            }

            // --- SUBIDA A CLOUDINARY ---
            $cloudinary = new Cloudinary('cloudinary://942191234587844:VmNYB6w4vj3DdLqI9SZSKVofOi0@dcj5rcpi8');

            // Caso retrocompatible (campo 'foto')
            if ($request->hasFile('foto')) {
                try {
                    $respuestaNube = $cloudinary->uploadApi()->upload($request->file('foto')->getRealPath(), [
                        'folder' => 'agente_servicios'
                    ]);
                    $servicio->evidence_path = $respuestaNube['secure_url'];
                } catch (\Exception $e) {
                    Log::error("Error subiendo evidencia a Cloudinary: " . $e->getMessage());
                }
            }

            // Evidencia 1
            if ($request->hasFile('evidencia_1')) {
                try {
                    $respuestaNube = $cloudinary->uploadApi()->upload($request->file('evidencia_1')->getRealPath(), [
                        'folder' => 'agente_servicios'
                    ]);
                    $servicio->evidence_path = $respuestaNube['secure_url'];
                } catch (\Exception $e) {
                    Log::error("Error subiendo evidencia_1: " . $e->getMessage());
                }
            }

            // Evidencia 2
            if ($request->hasFile('evidencia_2')) {
                try {
                    $respuestaNube = $cloudinary->uploadApi()->upload($request->file('evidencia_2')->getRealPath(), [
                        'folder' => 'agente_servicios'
                    ]);
                    // Intentamos guardar en evidence_path_2 si existe el atributo (vía asignación dinámica o si ya está en el modelo)
                    // Para evitar errores si la columna no existe aún, podemos usar un try catch o verificar Schema
                    $servicio->evidence_path_2 = $respuestaNube['secure_url'];
                } catch (\Exception $e) {
                    Log::error("Error subiendo evidencia_2: " . $e->getMessage());
                }
            }

            $servicio->service_category_id = $request->service_category_id ?? 1;
            $servicio->service_type = $request->service_type ?? $request->type ?? 'Mantenimiento';
            $servicio->priority = $request->priority ?? 'Media';
            $servicio->status = 'Por Asignar'; 
            
            // Generar título si no viene
            if (!$request->filled('title')) {
                $areaName = 'General';
                if ($request->property_area_id) {
                    $area = PropertyArea::find($request->property_area_id);
                    if ($area) $areaName = $area->name;
                }
                $servicio->title = "Reporte: " . $areaName;
            } else {
                $servicio->title = $request->title;
            }

            $servicio->supervisor_name = $request->supervisor_name;
            $servicio->description = $request->description;

            $servicio->assigned_to = $request->filled('technician_id') ? $request->technician_id : null;
            $servicio->scheduled_start = $request->filled('scheduled_start') ? $request->scheduled_start : ($request->filled('date') ? $request->date : now());
            $servicio->scheduled_end = $request->scheduled_end;

            $servicio->save();

            try {
                $admins = User::where('role_id', 0)->get();
                Notification::send($admins, new \App\Notifications\NewServiceRequested($servicio));
            } catch (\Exception $e) {
                \Log::error('Error al enviar notificación de nuevo servicio: ' . $e->getMessage());
            }

            if ($request->has('selected_components') && is_array($request->selected_components)) {
                $pivotData = [];
                foreach ($request->selected_components as $componentId) {
                    $pivotData[] = [
                        'service_id' => $servicio->id,
                        'property_component_id' => $componentId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                DB::table('service_component')->insert($pivotData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Servicio creado con éxito',
                'service' => $servicio
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Service::with(['property.client', 'technician'])->orderByDesc('created_at');

            if ($user && $user->role_id == 3) {
                $cliente = DB::table('clients')->where('user_id', $user->id)->first();
                
                if ($cliente) {
                    $query->whereHas('property', function($q) use ($cliente) {
                        $q->where('client_id', $cliente->id);
                    });
                } else {
                    return response()->json([], 200); 
                }
            }

            $servicios = $query->get();

            $formateados = $servicios->map(function ($s) {
                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'priority' => $s->priority,
                    'status' => $s->status,
                    'assigned_to' => $s->assigned_to,
                    'tecnico_nombre' => $s->technician
                        ? ($s->technician->first_name . ' ' . $s->technician->last_name)
                        : '⚠️ Por Asignar',

                    'propiedad_nombre' => $s->property
                        ? (strtoupper($s->property->type) . ' - ' . ($s->property->custom_curp ?? 'SIN CURP'))
                        : 'N/A',
                        
                    'cliente_nombre' => ($s->property && $s->property->client) ? $s->property->client->name : 'Usuario',

                    'direccion' => $s->property ? $s->property->address : 'N/A',
                    'fecha_programada' => $s->scheduled_start
                ];
            });

            return response()->json($formateados, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function assignTechnician(Request $request, $id)
    {
        try {
            $servicio = Service::with('property.client')->find($id);

            if (!$servicio) {
                return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }

            $servicio->assigned_to = $request->tecnico_id;
            $servicio->scheduled_start = $request->scheduled_start;
            $servicio->status = 'Programado';
            $servicio->save();

            if ($servicio->property && $servicio->property->client && $servicio->property->client->user_id) {
                $clienteUser = User::find($servicio->property->client->user_id);
                
                if ($clienteUser) {
                    Notification::send($clienteUser, new \App\Notifications\VisitRescheduled($servicio));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Técnico asignado y visita programada correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignWorkOrder(Request $request, $id)
    {
        try {
            $servicio = Service::find($id);

            if (!$servicio) {
                return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }

            $servicio->assigned_to = $request->tecnico_id;
            $servicio->scheduled_start = $request->scheduled_start;
            $servicio->custom_checklist = $request->custom_checklist; 
            $servicio->status = 'Programado';
            $servicio->save();

            if ($servicio->assigned_to) {
                $tecnicoUser = User::find($servicio->assigned_to);
                if ($tecnicoUser) {
                    \Illuminate\Support\Facades\Notification::send($tecnicoUser, new \App\Notifications\WorkAssigned($servicio));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Trabajo y Checklist asignados correctamente al técnico.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la asignación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $servicio = Service::with([
                'property.client',
                'technician',
                'property.areas.components',
                'property.areas.parent'
            ])->find($id);

            // Si no se encuentra en services, buscamos en work_orders
            if (!$servicio) {
                $workOrder = WorkOrder::with(['property.client', 'tecnico'])->find($id);
                if ($workOrder) {
                    return response()->json([
                        'id' => $workOrder->id,
                        'titulo' => $workOrder->type . ' - ' . $workOrder->zone,
                        'estado' => $workOrder->status,
                        'identificador_curp' => $workOrder->property ? $workOrder->property->custom_curp : 'S/N',
                        'propietario' => ($workOrder->property && $workOrder->property->client) ? $workOrder->property->client->name : 'Sin Propietario',
                        'direccion' => $workOrder->property ? $workOrder->property->address : 'Dirección no registrada',
                        'tecnico' => $workOrder->tecnico ? ($workOrder->tecnico->first_name . ' ' . $workOrder->tecnico->last_name) : 'Sin Asignar',
                        'custom_checklist' => $workOrder->custom_checklist,
                        'tipo_registro' => 'work_order'
                    ], 200);
                }
                return response()->json(['error' => 'Servicio o Orden de Trabajo no encontrada'], 404);
            }

            $datos = [
                'id' => $servicio->id,
                'titulo' => $servicio->title,
                'estado' => $servicio->status,
                'identificador_curp' => $servicio->property ? $servicio->property->custom_curp : 'S/N',
                'propietario' => ($servicio->property && $servicio->property->client) ? $servicio->property->client->name : 'Sin Propietario',
                'direccion' => $servicio->property ? $servicio->property->address : 'Dirección no registrada',
                'coordenadas' => $servicio->property ? $servicio->property->coordinates : null,
                'tipoPropiedad' => $servicio->property ? strtoupper($servicio->property->type) : 'N/A',
                'propiedad_nombre' => $servicio->property ? $servicio->property->property_name : 'Propiedad Sin Nombre',
                'foto_fachada' => $servicio->property ? $servicio->property->facade_photo_path : null,

                'tecnico' => $servicio->technician ? ($servicio->technician->first_name . ' ' . $servicio->technician->last_name) : 'Sin Asignar',
                'fecha_programada' => $servicio->scheduled_start ? date('d M, Y', strtotime($servicio->scheduled_start)) : 'Pendiente de programar',

                'secciones' => $servicio->property ? $servicio->property->areas->filter(function ($area) {
                    return $area->parent_id !== null || $area->components->count() > 0;
                })->map(function ($area) {
                    $categoriasRegistradas = DB::table('property_maintenance_categories')
                        ->where('property_area_id', $area->id)
                        ->pluck('name')
                        ->unique()
                        ->toArray();

                    $componentesPorCategoria = $area->components->groupBy('category');
                    $nombresCategorias = array_unique(array_merge($categoriasRegistradas, $componentesPorCategoria->keys()->toArray()));

                    return [
                        'id' => $area->id,
                        'titulo' => $area->name,
                        'zona_nombre' => $area->parent ? $area->parent->name : 'ÁREAS Y HABITACIONES',
                        'parent' => $area->parent ? [
                            'id' => $area->parent->id,
                            'name' => $area->parent->name
                        ] : null,
                        'descripcion' => $area->description,
                        'foto' => $area->image_path ? (str_starts_with($area->image_path, 'http') ? $area->image_path : asset('storage/' . $area->image_path)) : null,
                        'subSecciones' => collect($nombresCategorias)->map(function ($categoriaNombre) use ($componentesPorCategoria) {
                            $items = $componentesPorCategoria->get($categoriaNombre, collect());
                            return [
                                'nombre' => $categoriaNombre,
                                'nota' => 'Generado desde BD',
                                'inventario' => $items->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'nombre' => $item->sub_category ?? 'S/N',
                                        'categoria' => $item->sub_category ?? 'S/N', 
                                        'marca' => $item->brand,
                                        'modelo' => $item->model_or_color,
                                        'cantidad' => (int) $item->quantity,
                                        'estado' => $item->status,
                                        'observaciones' => $item->observations,
                                        'foto' => $item->image_path ? (str_starts_with($item->image_path, 'http') ? $item->image_path : asset('storage/' . $item->image_path)) : null,
                                        'foto_secundaria' => $item->image_path_secondary ? (str_starts_with($item->image_path_secondary, 'http') ? $item->image_path_secondary : asset('storage/' . $item->image_path_secondary)) : null,
                                        'galleries' => DB::table('component_galleries')->where('property_component_id', $item->id)->get()
                                    ];
                                })->values()
                            ];
                        })->values()
                    ];
                })->values() : []
            ];

            return response()->json($datos, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio no encontrado: ' . $e->getMessage()], 404);
        }
    }

    // ... (rest of the methods confirmedCitaCliente, solicitarReprogramacion, getTecnicoServicios, update remain unchanged)
    
    public function confirmarCitaCliente($id)
    {
        try {
            $servicio = Service::find($id);

            if (!$servicio) {
                return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }

            $servicio->status = 'Visita Confirmada';
            $servicio->save();

            $admins = User::where('role_id', 0)->get();
            
            Notification::send($admins, new \App\Notifications\VisitConfirmed($servicio));

            // Enviar notificación al Técnico asignado
            if ($servicio->assigned_to) {
                $tecnico = User::find($servicio->assigned_to);
                if ($tecnico) {
                    Notification::send($tecnico, new \App\Notifications\VisitConfirmed($servicio));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cita confirmada correctamente por el cliente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la cita: ' . $e->getMessage()
            ], 500);
        }
    }

    public function solicitarReprogramacion(Request $request, $id)
    {
        try {
            $servicio = Service::find($id);

            if (!$servicio) {
                return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }

            $request->validate([
                'fecha_sugerida' => 'required',
                'motivo' => 'nullable|string'
            ]);

            $notaReprogramacion = "\n[ALERTA DE REPROGRAMACIÓN]: El cliente solicita cambiar la visita al: " . 
                                  $request->fecha_sugerida . ". Motivo: " . 
                                  ($request->motivo ?? 'Sin motivo especificado.');
            
            $servicio->status = 'Reprogramación Solicitada';
            
            $servicio->description = $servicio->description . $notaReprogramacion;
            
            $servicio->save();

            $admins = User::where('role_id', 0)->get();
            
            Notification::send($admins, new \App\Notifications\RescheduleRequested($servicio, $request->fecha_sugerida));

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de reprogramación enviada.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar reprogramación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTecnicoServicios($idTecnico) {
        // 1. Obtener de la tabla 'services'
        $servicios = DB::table('services')
            ->join('properties', 'services.property_id', '=', 'properties.id')
            ->leftJoin('clients', 'properties.client_id', '=', 'clients.id')
            ->select(
                'services.*', 
                'properties.property_name', 
                'properties.address', 
                'properties.coordinates',
                'properties.facade_photo_path',
                'properties.custom_curp',
                'clients.name as client_name'
            )
            ->where('services.assigned_to', $idTecnico)
            ->get();

        // 2. Obtener de la tabla 'work_orders'
        $workOrders = DB::table('work_orders')
            ->join('properties', 'work_orders.property_id', '=', 'properties.id')
            ->leftJoin('clients', 'properties.client_id', '=', 'clients.id')
            ->select(
                'work_orders.*',
                'properties.property_name', 
                'properties.address', 
                'properties.coordinates',
                'properties.facade_photo_path',
                'properties.custom_curp',
                'clients.name as client_name'
            )
            ->where('work_orders.tecnico_id', $idTecnico)
            ->get();

        // 3. Unificar (Normalizando nombres de campos)
        $unificados = $servicios->map(function($s) {
            $s->tipo_registro = 'servicio';
            return $s;
        })->concat($workOrders->map(function($w) {
            $w->tipo_registro = 'work_order';
            // Mapear campos diferentes para que el frontend no rompa
            $w->assigned_to = $w->tecnico_id;
            $w->scheduled_start = $w->scheduled_at;
            // Si no tiene título, usamos el tipo o zona
            if (!isset($w->title) || !$w->title) {
                $w->title = ($w->type ?? 'Trabajo') . ' - ' . ($w->zone ?? 'General');
            }
            return $w;
        }));

        // --- LÓGICA DE DETECCIÓN DE ATRASOS ---
        $hoy = now();
        foreach ($unificados as $s) {
            $fechaProgramada = $s->scheduled_start ? \Carbon\Carbon::parse($s->scheduled_start) : null;
            
            if ($fechaProgramada && $fechaProgramada->isPast() && !in_array(strtolower($s->status), ['completed', 'finalizado', 'listo', 'completado'])) {
                
                $alreadyNotified = DB::table('notifications')
                    ->where('type', 'App\Notifications\TechnicianMissedVisitNotification')
                    ->where('data', 'like', '%"service_id":' . $s->id . '%')
                    ->where('created_at', '>=', now()->startOfDay())
                    ->exists();

                if (!$alreadyNotified) {
                    $admins = User::whereIn('role_id', [0, 1])->get();
                    $tecnico = User::find($idTecnico);
                    $tecnicoNombre = $tecnico ? $tecnico->first_name : 'Técnico';
                    
                    // Nota: Pasamos el objeto normalizado
                    Notification::send($admins, new TechnicianMissedVisitNotification($s, $tecnicoNombre));
                    Log::info("Notificación de visita no realizada enviada para el " . $s->tipo_registro . " #{$s->id}");
                }
            }
        }

        return response()->json($unificados);
    }

    public function update(Request $request, $id)
    {
        try {
            $servicio = Service::find($id);
            
            if (!$servicio) {
                $workOrder = WorkOrder::find($id);
                if ($workOrder) {
                    if ($request->has('status')) {
                        $workOrder->status = $request->status;
                    }
                    if ($request->has('custom_checklist')) {
                        $workOrder->custom_checklist = $request->custom_checklist;
                    }
                    $workOrder->save();
                    return response()->json(['success' => true, 'message' => 'Orden de Trabajo actualizada', 'work_order' => $workOrder], 200);
                }
                return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
            }

            if ($request->has('status')) {
                $servicio->status = $request->status;
                if ($request->status === 'completed' || $request->status === 'Finalizado') {
                    $servicio->real_end = now();
                }
            }
            
            $servicio->save();

            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente.',
                'service' => $servicio
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar servicio: ' . $e->getMessage()
            ], 500);
        }
    }
}
