<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PropertyController;

class PropertyComponentController extends Controller
{
    public function getCatalogSummary()
    {
        try {
            $summary = DB::table('property_components')
                ->select(
                    'brand',
                    'model_or_color',
                    'category',
                    DB::raw('SUM(quantity) as total_installed')
                )
                ->whereNotNull('brand')
                ->whereNotNull('model_or_color')
                ->groupBy('brand', 'model_or_color', 'category')
                ->orderByDesc('total_installed')
                ->get()
                ->map(function ($item, $index) {
                    return [
                        'id' => '#PROD-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                        'product_model' => $item->model_or_color,
                        'brand' => strtoupper($item->brand),
                        'category' => $item->category,
                        'total_installed' => (int) $item->total_installed,
                        'average_warranty' => '12 Months'
                    ];
                });

            return response()->json($summary, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving catalog summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getCatalogDetails(Request $request)
    {
        $brand = $request->query('brand');
        $model = $request->query('model');

        try {
            // 1. Buscamos todas las instalaciones de este modelo
            $installations = DB::table('property_components as pc')
                ->join('property_areas as pa', 'pc.property_area_id', '=', 'pa.id')
                ->join('properties as p', 'pa.property_id', '=', 'p.id')
                ->select('p.id as property_id', 'p.custom_curp', 'pa.name as area_name', 'pc.id as component_id', 'pc.created_at', 'pc.quantity')
                ->where('pc.brand', $brand)
                ->where('pc.model_or_color', $model)
                ->get();

            $ubicaciones = $installations->map(function ($inst) {
                // 2. BUSQUEDA EXACTA: Solo servicios vinculados a ESTE componente específico
                $reportes = DB::table('services as s')
                    ->join('service_component as sc', 's.id', '=', 'sc.service_id')
                    ->leftJoin('users as u', 's.assigned_to', '=', 'u.id')
                    ->select('s.scheduled_start', 's.service_type', 'u.first_name', 's.status')
                    ->where('sc.property_component_id', $inst->component_id) // <--- El candado de precisión
                    ->orderByDesc('s.scheduled_start')
                    ->get()
                    ->map(function ($service) {
                        return [
                            'fecha' => date('d/m/Y', strtotime($service->scheduled_start)),
                            'tecnico' => $service->first_name ?? 'Técnico Agente',
                            'tipo' => $service->service_type,
                            'vencimiento' => 'N/A'
                        ];
                    });

                return [
                    'nombre' => $inst->custom_curp . ' - ' . $inst->area_name,
                    'totalInstalados' => (int) $inst->quantity,
                    'reportes' => $reportes
                ];
            });

            return response()->json([
                'proveedor' => 'Proveedor Master Agente',
                'ubicaciones' => $ubicaciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getComponentsByProperty($propertyId)
    {
        try {
            $components = DB::table('property_components as pc')
                ->join('property_areas as pa', 'pc.property_area_id', '=', 'pa.id')
                ->select(
                    'pc.id',
                    'pc.brand',
                    'pc.model_or_color as model',
                    'pa.name as area_name',
                    'pc.category'
                )
                ->where('pa.property_id', $propertyId)
                ->get()
                ->map(function ($item) {
                    // Formateamos para que React lo lea fácil
                    return [
                        'id' => $item->id,
                        // Ej: "Inverter X32 (MIRAGE) - Ubicación: Recámara Principal"
                        'display_name' => "{$item->model} ({$item->brand}) - Ubicación: {$item->area_name}"
                    ];
                });

            return response()->json($components, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los componentes de la propiedad',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}