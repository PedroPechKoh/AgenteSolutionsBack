<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialty;
use App\Models\User;

class SpecialtyController extends Controller
{
    /**
     * Obtener todas las especialidades disponibles
     */
    public function index()
    {
        $specialties = Specialty::orderBy('name')->get();
        return response()->json($specialties, 200);
    }

    /**
     * Crear una nueva especialidad
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191|unique:specialties',
            'icon' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:191'
        ]);

        $spec = Specialty::create([
            'name' => trim($request->name),
            'icon' => $request->icon ?? '🔧',
            'category' => $request->category ?? 'General'
        ]);

        return response()->json(['success' => true, 'specialty' => $spec], 201);
    }

    /**
     * Actualizar las especialidades de un técnico
     */
    public function syncUserSpecialties(Request $request, $userId)
    {
        $realId = str_replace('u_', '', $userId);
        $user = User::find($realId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $specialties = $request->input('specialties', []);
        $specialtyIds = [];

        foreach ((array) $specialties as $specItem) {
            if (is_numeric($specItem)) {
                $specialtyIds[] = (int) $specItem;
            } elseif (is_string($specItem) && trim($specItem) !== '') {
                $specObj = Specialty::firstOrCreate(
                    ['name' => trim($specItem)],
                    ['icon' => '🔧', 'category' => 'General']
                );
                $specialtyIds[] = $specObj->id;
            }
        }

        $user->specialties()->sync($specialtyIds);

        return response()->json([
            'success' => true,
            'message' => 'Especialidades actualizadas correctamente.',
            'specialties' => $user->specialties
        ], 200);
    }
}
