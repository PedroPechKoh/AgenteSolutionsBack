<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Property;
use App\Models\Quote;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\Client;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    /**
     * Listar todas las empresas activas para el portal público de Registro/Login.
     */
    public function listTenants()
    {
        $tenants = Tenant::where('status', 'active')
            ->select('id', 'name', 'code', 'logo_url', 'phone', 'email')
            ->get();

        return response()->json([
            'success' => true,
            'tenants' => $tenants
        ]);
    }

    /**
     * Solicitar membresía para convertirse en Autónomo (Empresa).
     */
    public function requestMembership(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        // Crear registro de tenant en estado pendiente
        // Generar un código temporal que se confirmará al autorizar
        $tempCode = 'PENDING_' . time() . '_' . $user->id;

        $tenant = Tenant::create([
            'name' => $request->company_name,
            'code' => $tempCode,
            'owner_user_id' => $user->id,
            'phone' => $request->phone ?? $user->phone_number,
            'email' => $request->email ?? $user->email,
            'status' => 'pending_approval',
            'membership_type' => $request->membership_type ?? 'standard'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de membresía enviada correctamente. En espera de autorización del Root.',
            'tenant' => $tenant
        ], 201);
    }

    /**
     * Listar solicitudes de membresía pendientes (Solo Root: role_id = 0).
     */
    public function pendingMemberships()
    {
        if (auth()->user()->role_id !== 0) {
            return response()->json(['error' => 'No tienes permisos de Root'], 403);
        }

        $pending = Tenant::with('owner')->where('status', 'pending_approval')->get();

        return response()->json([
            'success' => true,
            'pending_tenants' => $pending
        ]);
    }

    /**
     * Aprobar una empresa/Autónomo por parte del Root.
     * Le otorga el role_id = 4 al dueño y genera su código AUT_01, AUT_02, etc.
     */
    public function approveTenant(Request $request, $id)
    {
        if (auth()->user()->role_id !== 0) {
            return response()->json(['error' => 'No tienes permisos de Root'], 403);
        }

        $tenant = Tenant::findOrFail($id);

        // Generar código AUT_XX
        $code = 'AUT_' . str_pad($tenant->id, 2, '0', STR_PAD_LEFT);
        
        $tenant->update([
            'status' => 'active',
            'code' => $code
        ]);

        // Actualizar al dueño con el rol 4 (Autónomo) y asignarle su tenant_id
        if ($tenant->owner_user_id) {
            DB::table('roles')->insertOrIgnore([
                'id' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            User::withoutGlobalScopes()->where('id', $tenant->owner_user_id)->update([
                'role_id' => 4, // 4 = Autónomo
                'tenant_id' => $tenant->id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Autónomo autorizado exitosamente con el código {$code}",
            'tenant' => $tenant
        ]);
    }

    /**
     * Traspasar cartera completa de un Autónomo a otro (o a Root).
     */
    public function transferPortfolio(Request $request)
    {
        if (auth()->user()->role_id !== 0) {
            return response()->json(['error' => 'No tienes permisos de Root'], 403);
        }

        $request->validate([
            'from_tenant_id' => 'required|integer',
            'to_tenant_id' => 'nullable|integer', // Si es null o 0, pasa a Root
        ]);

        $from = $request->from_tenant_id;
        $to = $request->to_tenant_id ?: null;

        DB::beginTransaction();
        try {
            // Actualizar masivamente en todas las tablas sin aplicar Global Scopes
            User::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);
            Property::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);
            Quote::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);
            Service::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);
            WorkOrder::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);
            Client::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);
            Technician::withoutGlobalScopes()->where('tenant_id', $from)->update(['tenant_id' => $to]);

            // Desactivar o suspender al tenant viejo si se desea
            if ($request->suspend_old_tenant) {
                Tenant::where('id', $from)->update(['status' => 'suspended']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cartera de clientes, técnicos, propiedades y cotizaciones transferida con éxito.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al transferir la cartera: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Listar técnicos en espera de autorización en la empresa del usuario logueado.
     */
    public function pendingTechnicians()
    {
        $user = auth()->user();
        
        $query = User::withoutGlobalScopes()
            ->where('role_id', 2)
            ->where('approval_status', 'pending');

        if ($user->role_id !== 0) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $technicians = $query->select('id', 'first_name', 'last_name', 'email', 'phone_number', 'created_at', 'tenant_id')->get();

        return response()->json([
            'success' => true,
            'pending_technicians' => $technicians
        ]);
    }

    /**
     * Aprobar a un técnico que solicitó unirse al equipo (Sala de espera).
     */
    public function approveTechnician(Request $request, $userId)
    {
        $currentUser = auth()->user();
        
        $technician = User::withoutGlobalScopes()->findOrFail($userId);

        // Verificar que el técnico pertenezca al mismo tenant del autónomo o que sea Root
        if ($currentUser->role_id !== 0 && $technician->tenant_id !== $currentUser->tenant_id) {
            return response()->json(['error' => 'No tienes permisos sobre este técnico'], 403);
        }

        $technician->update([
            'approval_status' => 'approved',
            'is_active' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Técnico autorizado y dado de alta exitosamente.',
            'technician' => $technician
        ]);
    }
}
