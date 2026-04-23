<?php

namespace App\Http\Controllers;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\User;
use App\Models\Service; // Asegúrate de importar el modelo Service
use App\Notifications\QuoteStatusUpdated;

class QuoteController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validamos lo básico
            $request->validate([
                'service_id' => 'required|exists:services,id',
                'type' => 'required|in:manual,archivo',
            ]);

            $quote = new Quote();
            $quote->service_id = $request->service_id;
            $quote->type = $request->type;
            $quote->status = 'Pendiente'; // Estado por defecto

            // Si es manual, guardamos los textos
            if ($request->type === 'manual') {
                $quote->concept = $request->concept;
                $quote->estimated_amount = $request->estimated_amount;
                $quote->validity_days = $request->validity_days ?? 15;
                $quote->observations = $request->observations;
            }
            // Si es archivo, subimos el documento
            else {
                if ($request->hasFile('file')) {
                    // Guarda en storage/app/public/quotes
                    $path = $request->file('file')->store('quotes', 'public');
                    $quote->file_path = $path;
                } else {
                    return response()->json(['error' => 'No se adjuntó ningún archivo'], 400);
                }
            }

            $quote->save();

            return response()->json(['message' => 'Cotización guardada exitosamente', 'quote' => $quote], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        try {
            // Traemos las cotizaciones, ordenadas por la más reciente
            $quotes = Quote::with(['service.property.client', 'service.technician'])
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->map(function($quote) {
                                return [
                                    'id' => $quote->id,
                                    'service_id' => $quote->service_id, // Útil para vincular en React
                                    'folio' => '#' . str_pad($quote->id, 4, '0', STR_PAD_LEFT),
                                    'cliente' => $quote->service->property->client->name ?? 'Sin Cliente',
                                    'tecnico' => $quote->service->technician ? ($quote->service->technician->first_name . ' ' . $quote->service->technician->last_name) : 'Sin Técnico',
                                    'fecha' => $quote->created_at->format('Y-m-d'),
                                    'total' => $quote->estimated_amount ?? 0,
                                    'estado' => $quote->status,
                                    'tipo' => $quote->type,
                                    'concepto' => $quote->concept,
                                    'observaciones' => $quote->observations,
                                    'archivo_url' => $quote->file_path ? asset('storage/' . $quote->file_path) : null
                                ];
                            });

            return response()->json($quotes, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar cotizaciones: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:Aprobado,Rechazado',
                'rejection_reason' => 'nullable|string'
            ]);

            $quote = Quote::findOrFail($id);
            $quote->status = $request->status;

            if ($request->status === 'Rechazado' && $request->filled('rejection_reason')) {
                $quote->observations = $quote->observations . "\n\n[MOTIVO RECHAZO]: " . $request->rejection_reason;
            }

            // --- LÓGICA DE FLUJO: SI SE APRUEBA, ACTIVAMOS EL SERVICIO ---
            if ($request->status === 'Aprobado') {
                $service = Service::find($quote->service_id);
                if ($service) {
                    $service->update([
                        'status' => 'Programado', // Cambia el estado del servicio
                        'quote_approved' => true   // Marcamos que tiene cotización aceptada
                    ]);
                }
            }

            $quote->save();

            // Notificar admins
            $clientName = $quote->service->property->client->name ?? 'Cliente desconocido';
            $admins = User::where('role_id', 0)->get();
            foreach ($admins as $admin) {
                $admin->notify(new QuoteStatusUpdated($quote, $clientName));
            }

            return response()->json(['message' => 'Estado actualizado y servicio vinculado', 'quote' => $quote], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Nuevo método para que el técnico confirme el check-list
     */
    public function confirmMaterials($serviceId)
    {
        try {
            $service = Service::findOrFail($serviceId);

            $service->update([
                'materials_checked' => true
            ]);

            return response()->json([
                'message' => 'Check-list de materiales confirmado',
                'materials_checked' => true
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al confirmar materiales: ' . $e->getMessage()], 500);
        }
    }

    public function updateObservations(Request $request, $id)
{
    $quote = Quote::findOrFail($id);
    // Tu tabla usa 'observations'
    $quote->observations = $request->input('observaciones'); 
    $quote->save();

    return response()->json(['message' => 'Observaciones guardadas']);
}

public function finalizarCotizacion(Request $request, $id)
    {
        $quote = \App\Models\Quote::findOrFail($id);

        try {
            // Si viene un archivo PDF, usamos la "Opción Nuclear"
            if ($request->hasFile('pdf')) {
                // Instanciamos Cloudinary directamente con tu clave (igual que en ImageController)
                $cloudinary = new Cloudinary('cloudinary://942191234587844:VmNYB6w4vj3DdLqI9SZSKVofOi0@dcj5rcpi8');
                
                // Subimos el archivo a la carpeta 'cotizaciones_pdf'
                // NOTA CRÍTICA: Se debe especificar resource_type => 'raw' o 'auto' para PDFs, de lo contrario falla
                $respuestaNube = $cloudinary->uploadApi()->upload($request->file('pdf')->getRealPath(), [
                    'folder' => 'cotizaciones_pdf',
                    'resource_type' => 'auto' 
                ]);

                // Guardamos la URL segura
                $quote->file_path = $respuestaNube['secure_url'];
            }

            // Guardamos las observaciones
            $quote->observations = $request->input('observaciones');
            
            // Opcional: Cambiar estado (ej. de 'Pendiente' a 'Aprobado' o 'En Proceso')
            // $quote->status = 'En Proceso';

            $quote->save();

            return response()->json([
                'message' => 'Cotización generada y guardada correctamente',
                'url' => $quote->file_path
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno al subir PDF: ' . $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ], 500);
        }
    }
}
