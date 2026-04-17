<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PropertyArea;
use App\Models\PropertyComponent;
use Illuminate\Support\Facades\Log;

class PropertyAreaController extends Controller
{
    /**
     * Obtener todas las áreas de una propiedad específica.
     * GET /api/properties/{id}/areas
     */
    public function getByProperty($propertyId)
    {
        try {
            $areas = PropertyArea::where('property_id', $propertyId)
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json($areas, 200);
        } catch (\Exception $e) {
            Log::error("Error en getByProperty: " . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener las áreas',
                'description' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una nueva zona.
     * POST /api/property-areas
     */
    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'name' => 'required|string|max:191',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validación de imagen

        ]);

        $area = new PropertyArea();
        $area->property_id = $request->property_id;
        $area->name = $request->name;
        $area->parent_id = $request->parent_id ?? null;
        $area->description = $request->description ?? '';

        // LÓGICA PARA LA IMAGEN
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Se guarda en storage/app/public/zonas
            $path = $file->storeAs('zonas', $filename, 'public');
            $area->image_path = $path;
        }

        $area->save();

        return response()->json([
            'success' => true,
            'area' => $area
        ], 201);
    }
    public function getSubAreas($parentId)
    {
        $subareas = PropertyArea::where('parent_id', $parentId)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($subareas, 200);
    }

    /**
     * Actualizar una zona (imagen o descripción).
     * PUT /api/property-areas/{id}
     */
    public function update(Request $request, $id)
    {
        $area = PropertyArea::findOrFail($id);

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string'
        ]);

        if ($request->has('description')) {
            $area->description = $request->description;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('zonas', $filename, 'public');
            $area->image_path = $path;
        }

        $area->save();

        return response()->json([
            'success' => true,
            'area' => $area
        ], 200);
    }
}