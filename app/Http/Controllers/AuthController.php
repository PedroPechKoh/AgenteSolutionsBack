<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registro(Request $request)
    {
        // 1. Actualizamos la validación para requerir first_name y last_name
        $request->validate([
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer',
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number',
            'company_code' => 'nullable|string',
            'tenant_id' => 'nullable|integer',
            'company_name' => 'nullable|string|max:191'
        ], [
            'email.unique' => 'Este correo electrónico ya está registrado en otra cuenta.',
            'phone_number.unique' => 'Este número de teléfono ya está registrado en otra cuenta.'
        ]);

        // Buscar tenant por código o ID
        $tenantId = $request->tenant_id ?? null;
        if (!empty($request->company_code)) {
            $t = Tenant::where('code', $request->company_code)
                       ->orWhere('phone', $request->company_code)
                       ->first();
            if ($t) {
                $tenantId = $t->id;
            }
        }

        // Si es Técnico (rol 2), entra en sala de espera inactivo
        $isTechnician = ($request->role_id == 2);
        $isAutonomo = ($request->role_id == 4 || !empty($request->company_name));
        $approvalStatus = $isTechnician ? 'pending' : 'approved';
        $isActive = $isTechnician ? 0 : 1;

        // Por seguridad, si solicita ser Autónomo inicia como Cliente (3) hasta autorización del Root
        $roleToAssign = $isAutonomo ? 3 : $request->role_id;

        // 2. Guardamos usando los campos correctos de la tabla
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $roleToAssign,
            'tenant_id' => $tenantId,
            'approval_status' => $approvalStatus,
            'phone_number' => $request->phone_number ?? null,
            'is_active' => $isActive
        ]);

        if ($isAutonomo) {
            Tenant::create([
                'name' => $request->company_name ?? ($request->first_name . ' ' . $request->last_name),
                'code' => 'PENDING_' . time() . '_' . $user->id,
                'owner_user_id' => $user->id,
                'phone' => $user->phone_number,
                'email' => $user->email,
                'status' => 'pending_approval',
                'membership_type' => 'autonomo'
            ]);
        }

        if ($isTechnician) {
            return response()->json([
                'success' => true,
                'status' => 'pending_approval',
                'message' => 'Tu perfil ha sido registrado y está en espera de ser revisado y autorizado por el Administrador de tu empresa.',
                'user' => $user
            ], 201);
        }

        $token = $user->createToken('AgenteToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::withoutGlobalScopes()
                    ->where('email', $request->email)
                    ->orWhere('phone_number', $request->email)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas. Verifica tus datos y contraseña.'
            ], 401);
        }

        if ($user->approval_status === 'pending') {
            return response()->json([
                'error' => 'Tu perfil de Técnico ha sido registrado y está en espera de ser revisado y autorizado por el Administrador de tu empresa.'
            ], 403);
        }

        if ($user->is_active == 0) {
            return response()->json([
                'error' => 'No puedes acceder a tu cuenta, por favor contactate con el servicio de soporte.'
            ], 403);
        }

        $token = $user->createToken('AgenteToken')->plainTextToken;
        $user->load('tenant');

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }

    public function recoverPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'new_password' => 'required|string|min:6'
        ], [
            'email.exists' => 'No encontramos ninguna cuenta con ese correo electrónico.'
        ]);

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.'
        ]);
    }
}