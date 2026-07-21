<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PropertyManager;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

class PropertyManagerController extends Controller
{
    public function linkByCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = auth('sanctum')->user();
        if ($user->role_id != 7) {
            return response()->json(['error' => 'Solo administradores de propiedades pueden vincularse.'], 403);
        }

        $tenant = Tenant::where('code', $request->code)->first();
        if (!$tenant) {
            return response()->json(['error' => 'Código de Autónomo no válido.'], 404);
        }

        // Check if Autonomo already has an active manager
        $existingManager = PropertyManager::where('autonomo_user_id', $tenant->owner_user_id)
                                          ->where('status', 'active')
                                          ->first();
        if ($existingManager) {
            return response()->json(['error' => 'Este Autónomo ya tiene un administrador vinculado.'], 400);
        }

        // Link
        PropertyManager::updateOrCreate(
            ['manager_user_id' => $user->id, 'autonomo_user_id' => $tenant->owner_user_id],
            [
                'tenant_id' => $tenant->id,
                'status' => 'active',
                'linked_at' => now(),
                'revoked_at' => null,
                'grace_period_until' => null
            ]
        );

        $user->tenant_id = $tenant->id;
        $user->is_active = 1;
        $user->approval_status = 'approved';
        $user->save();

        return response()->json(['message' => 'Vinculado exitosamente.', 'tenant' => $tenant]);
    }

    public function unlink(Request $request)
    {
        $user = auth('sanctum')->user();
        $manager = PropertyManager::where('autonomo_user_id', $user->id)->where('status', 'active')->first();
        
        if (!$manager) {
            return response()->json(['error' => 'No tienes un administrador activo.'], 404);
        }

        $manager->status = 'suspended';
        $manager->revoked_at = now();
        $manager->grace_period_until = now()->addDays(30);
        $manager->save();

        // Admin loses tenant access
        $adminUser = User::find($manager->manager_user_id);
        if ($adminUser) {
            $adminUser->tenant_id = null;
            $adminUser->approval_status = 'suspended';
            $adminUser->save();
        }

        return response()->json(['message' => 'Administrador desvinculado.']);
    }

    public function getMyManager(Request $request)
    {
        $user = auth('sanctum')->user();
        $manager = PropertyManager::with(['manager', 'assignedProperties'])
                                  ->where('autonomo_user_id', $user->id)
                                  ->where('status', 'active')
                                  ->first();
        return response()->json($manager);
    }

    public function getMyStatus(Request $request)
    {
        $user = auth('sanctum')->user();
        $manager = PropertyManager::with('autonomo')->where('manager_user_id', $user->id)->orderBy('id', 'desc')->first();
        return response()->json($manager);
    }

    public function assignProperties(Request $request)
    {
        $user = auth('sanctum')->user();
        $manager = PropertyManager::where('autonomo_user_id', $user->id)->where('status', 'active')->first();
        if (!$manager) {
            return response()->json(['error' => 'No hay administrador activo.'], 404);
        }

        $manager->assignedProperties()->sync($request->property_ids ?? []);
        return response()->json(['message' => 'Propiedades asignadas.']);
    }
}
