<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\PropertyComponent;

class PropertyComponentController extends Controller
{
    public function getByArea($areaId)
    {
        try {
            $components = DB::table('property_components')
                ->where('property_area_id', $areaId)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($components as $component) {
                $component->galleries = DB::table('component_galleries')
                    ->where('property_component_id', $component->id)
                    ->get();
            }

            return response()->json($components, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validación (agregamos la imagen)
            $request->validate([
                'property_area_id' => 'required',
                'category' => 'required|string',
                'sub_category' => 'required|string',
                'quantity' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            // Lógica para la imagen
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('componentes', $filename, 'public');
            }

            // Insertar en BD
            $id = DB::table('property_components')->insertGetId([
                'property_area_id' => $request->property_area_id,
                'category' => $request->category,
                'sub_category' => $request->sub_category,
                'brand' => $request->brand ?? '',
                'model_or_color' => $request->model_or_color ?? '',
                'serial_number' => $request->serial_number ?? '',
                'quantity' => $request->quantity,
                'unit' => $request->unit ?? 'PZA',
                'status' => $request->status ?? 'Bueno',
                'observations' => $request->observations ?? '',
                'image_path' => $imagePath, // Guardamos la ruta
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('componentes/galeria', $filename, 'public');

                    DB::table('component_galleries')->insert([
                        'property_component_id' => $id,
                        'image_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json(['success' => true, 'id' => $id], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ==========================================================
     * TUS FUNCIONES ORIGINALES (SIN MODIFICAR)
     * ==========================================================
     */

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
    public function destroy($id)
    {
        try {
            DB::table('property_components')->where('id', $id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $component = DB::table('property_components')->where('id', $id)->first();
            if (!$component) {
                return response()->json(['error' => 'No encontrado'], 404);
            }

            // Lógica de la imagen (Si mandan una nueva, reemplaza la vieja)
            $imagePath = $component->image_path;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $imagePath = $file->storeAs('componentes', $filename, 'public');
            }

            DB::table('property_components')->where('id', $id)->update([
                'sub_category' => $request->sub_category,
                'brand' => $request->brand ?? '',
                'model_or_color' => $request->model_or_color ?? '',
                'serial_number' => $request->serial_number ?? '',
                'quantity' => $request->quantity,
                'observations' => $request->observations ?? '',
                'image_path' => $imagePath,
                'updated_at' => now(),
            ]);

            // Guardar nuevas fotos en la galería al editar
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('componentes/galeria', $filename, 'public');

                    DB::table('component_galleries')->insert([
                        'property_component_id' => $id,
                        'image_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}