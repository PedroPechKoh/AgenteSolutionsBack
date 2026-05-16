<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyCategoryController extends Controller
{
    public function getByArea($areaId)
    {
        try {
            // ✅ Protección de Sanctum (Gafete requerido)
            $user = auth('sanctum')->user();
            if (!$user) return response()->json(['error' => 'No autorizado'], 401);

            $categories = DB::table('property_maintenance_categories')
                ->where('property_area_id', $areaId)
                ->get();
            return response()->json($categories, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // ✅ Protección de Sanctum (Gafete requerido)
            $user = auth('sanctum')->user();
            if (!$user) return response()->json(['error' => 'No autorizado'], 401);

            $request->validate([
                'property_area_id' => 'required',
                'name' => 'required|string|max:100'
            ]);

            $id = DB::table('property_maintenance_categories')->insertGetId([
                'property_area_id' => $request->property_area_id,
                'name' => strtoupper($request->name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'id' => $id], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) return response()->json(['error' => 'No autorizado'], 401);

            $request->validate([
                'name' => 'required|string|max:100'
            ]);

            $cat = DB::table('property_maintenance_categories')->where('id', $id)->first();
            if ($cat) {
                // Actualizamos los componentes para que reflejen el nuevo nombre de categoría
                DB::table('property_components')
                    ->where('property_area_id', $cat->property_area_id)
                    ->where('category', $cat->name)
                    ->update([
                        'category' => strtoupper($request->name),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('property_maintenance_categories')
                ->where('id', $id)
                ->update([
                    'name' => strtoupper($request->name),
                    'updated_at' => now(),
                ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) return response()->json(['error' => 'No autorizado'], 401);

            $cat = DB::table('property_maintenance_categories')->where('id', $id)->first();
            if ($cat) {
                // Eliminar componentes asociados usando el nombre y área
                DB::table('property_components')
                    ->where('property_area_id', $cat->property_area_id)
                    ->where('category', $cat->name)
                    ->delete();
            }
            
            DB::table('property_maintenance_categories')->where('id', $id)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}