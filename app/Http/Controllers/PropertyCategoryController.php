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
}