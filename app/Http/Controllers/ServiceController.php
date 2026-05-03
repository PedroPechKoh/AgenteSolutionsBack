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

class ServiceController extends Controller
{
    // ---------------------------------------------------
    // 1. CREAR / SOLICITAR LEVANTAMIENTO
    // ---------------------------------------------------
    public function store(Request $request)
    {
        try {
            $request->validate([
                'property_id' => 'required|exists:properties,id',
                'title' => 'required|string|max:191',
            ]);

            $servicio = new Service();
            $servicio->property_id = $request->property_id;

            $user = $request->user();
            if ($user && $user->role_id == 3) {
                 $servicio->requested_by = $user->id;
            } else {
                 $servicio->requested_by = $request->filled('requested_by') ? $request->requested_by : null;
            }

            $servicio->service_category_id = $request->service_category_id ?? 1;
            $servicio->service_type = $request->service_type ?? $request->type ?? 'Mantenimiento';
            $servicio->priority = $request->priority ?? 'Media';
            $servicio->status = 'Por Asignar'; 
            $servicio->title = $request->title;
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

    // ---------------------------------------------------
    // 2. LISTAR LEVANTAMIENTOS (PARA LA TABLA)
    // ---------------------------------------------------
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

    // ---------------------------------------------------
    // 3. ASIGNAR TÉCNICO Y PROGRAMAR VISITA
    // ---------------------------------------------------
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
                    Notification::send($clienteUser, new VisitRescheduled($servicio));
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

    // ---------------------------------------------------
    // ASIGNAR TRABAJO (CON CHECKLIST) A TECNICO
    // ---------------------------------------------------
    public function assignWorkOrder(Request $request, $id)
    {
        try {
            $servicio = Service::find($id);

            if (!$servicio) {
                return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }

            $servicio->assigned_to = $request->tecnico_id;
            $servicio->scheduled_start = $request->scheduled_start;
            $servicio->custom_checklist = $request->custom_checklist; // El JSON del checklist
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

    // ---------------------------------------------------
    // 4. VER DETALLES DE UN REPORTE ESPECÍFICO
    // ---------------------------------------------------
    public function show($id)
    {
        try {
            $servicio = Service::with([
                'property.client',
                'technician',
                'property.areas.components',
                'property.areas.parent'
            ])->findOrFail($id);

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
                    // Solo devolver áreas que son cuartos (tienen padre) o que tengan componentes directamente
                    return $area->parent_id !== null || $area->components->count() > 0;
                })->map(function ($area) {
                    return [
                        'titulo' => $area->name,
                        'zona_nombre' => $area->parent ? $area->parent->name : 'ÁREAS Y HABITACIONES',
                        'descripcion' => $area->description,
                        'foto' => $area->image_path ? (str_starts_with($area->image_path, 'http') ? $area->image_path : asset('storage/' . $area->image_path)) : null,
                        'subSecciones' => $area->components->groupBy('category')->map(function ($items, $categoriaNombre) {
                            return [
                                'nombre' => $categoriaNombre,
                                'nota' => 'Generado desde BD',
                                'inventario' => $items->map(function ($item) {
                                    return [
                                        'nombre' => $item->sub_category ?? 'S/N',
                                        'categoria' => $item->sub_category ?? 'S/N', // Mantenemos por compatibilidad con el frontend
                                        'marca' => $item->brand,
                                        'modelo' => $item->model_or_color,
                                        'cantidad' => (int) $item->quantity,
                                        'estado' => $item->status,
                                        'observaciones' => $item->observations,
                                        'foto' => $item->image_path ? (str_starts_with($item->image_path, 'http') ? $item->image_path : asset('storage/' . $item->image_path)) : null
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
    // ---------------------------------------------------
    // 5. CLIENTE CONFIRMA LA CITA
    // ---------------------------------------------------
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
            
            Notification::send($admins, new VisitConfirmed($servicio));

            // Enviar notificación al Técnico asignado
            if ($servicio->assigned_to) {
                $tecnico = User::find($servicio->assigned_to);
                if ($tecnico) {
                    Notification::send($tecnico, new VisitConfirmed($servicio));
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
            
            Notification::send($admins, new RescheduleRequested($servicio, $request->fecha_sugerida));

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
    ////Asignar trabajos al tencico:
    public function getTecnicoServicios($idTecnico) {
        // Usamos Join para traer los datos de la propiedad y su dueño/cliente
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

        return response()->json($servicios);
    }

    // ---------------------------------------------------
    // 6. ACTUALIZAR ESTADO DEL SERVICIO (FINALIZAR)
    // ---------------------------------------------------
    public function update(Request $request, $id)
    {
        try {
            $servicio = Service::findOrFail($id);

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
