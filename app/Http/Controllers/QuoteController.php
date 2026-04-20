<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\User;
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
            // Y con 'with' jalamos la información del servicio asociado (cliente y técnico)
            $quotes = Quote::with(['service.property.client', 'service.technician'])
                           ->orderBy('created_at', 'desc')
                           ->get()
                           ->map(function($quote) {
                               // Formateamos la respuesta para que a React se le haga más fácil
                               return [
                                   'id' => $quote->id,
                                   'folio' => '#' . str_pad($quote->id, 4, '0', STR_PAD_LEFT),
                                   'cliente' => $quote->service->property->client->name ?? 'Sin Cliente',
                                   'tecnico' => $quote->service->technician ? ($quote->service->technician->first_name . ' ' . $quote->service->technician->last_name) : 'Sin Técnico',
                                   'fecha' => $quote->created_at->format('Y-m-d'),
                                   'total' => $quote->estimated_amount ?? 0,
                                   'estado' => $quote->status,
                                   'tipo' => $quote->type,
                                   'concepto' => $quote->concept,
                                   'observaciones' => $quote->observations,
                                   // Si hay archivo, armamos la URL completa
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

            $quote->save();

            // Notify admins
            $clientName = $quote->service->property->client->name ?? 'Cliente desconocido';
            $admins = User::where('role_id', 0)->get();
            foreach ($admins as $admin) {
                $admin->notify(new QuoteStatusUpdated($quote, $clientName));
            }

            return response()->json(['message' => 'Estado actualizado', 'quote' => $quote], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }
}