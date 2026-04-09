<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 

class UserController extends Controller
{
    
    public function updateProfile(Request $request)
    {
        try {
            $idWithPrefix = $request->input('id'); 
            $profilePicturePath = null;

            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            }

            // ---------------------------------------------------------
            // 1. MANEJO PARA CLIENTES (Tabla 'clients')
            // ---------------------------------------------------------
            if (str_starts_with($idWithPrefix, 'c_')) {
                $realId = str_replace('c_', '', $idWithPrefix);
                $cliente = DB::table('clients')->where('id', $realId)->first();

                if (!$cliente) {
                    return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
                }

                $fullName = trim($request->input('first_name') . ' ' . $request->input('last_name'));

                $updateData = [
                    'name' => $fullName,
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone_number'),
                    'is_active' => $request->input('is_active'), 
                    'updated_at' => now(),
                ];

                if ($profilePicturePath) {
                    $updateData['profile_picture'] = $profilePicturePath;
                    if ($cliente->profile_picture && !str_starts_with($cliente->profile_picture, 'http')) {
                        Storage::disk('public')->delete($cliente->profile_picture);
                    }
                }

                DB::table('clients')->where('id', $realId)->update($updateData);

                $clienteActualizado = DB::table('clients')->where('id', $realId)->first();
                $fotoUrl = $clienteActualizado->profile_picture 
                    ? (str_starts_with($clienteActualizado->profile_picture, 'http') ? $clienteActualizado->profile_picture : asset('storage/' . $clienteActualizado->profile_picture)) 
                    : null;

                return response()->json([
                    'success' => true, 
                    'message' => 'Expediente del cliente actualizado.',
                    'new_picture_url' => $fotoUrl
                ], 200);
            }

            // ---------------------------------------------------------
            // 2. MANEJO PARA USUARIOS (Tabla 'users')
            // ---------------------------------------------------------
            $realId = str_replace('u_', '', $idWithPrefix);
            $user = User::find($realId);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }

            if ($user->role_id === 0) {
                return response()->json(['success' => false, 'message' => 'Acceso Denegado: No puedes editar los datos del ROOT.'], 403);
            }

            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name') ?: ''; 
            $user->email = $request->input('email');
            $user->phone_number = $request->input('phone_number');

            if ($request->filled('password')) {
                $user->password = bcrypt($request->input('password'));
            }

            if ($profilePicturePath) {
                if ($user->profile_picture && !str_starts_with($user->profile_picture, 'http')) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $user->profile_picture = $profilePicturePath;
            }

            $user->save();

            $fotoUrl = $user->profile_picture 
                ? (str_starts_with($user->profile_picture, 'http') ? $user->profile_picture : asset('storage/' . $user->profile_picture)) 
                : null;

            return response()->json([
                'success' => true, 
                'message' => 'Expediente del usuario actualizado.',
                'new_picture_url' => $fotoUrl
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // OTRAS FUNCIONES (Rol, Listar, Eliminar, Bloquear)
    // =========================================================================
    public function updateRole(Request $request, $id)
    {
        $request->validate(['role_id' => 'required|integer|in:0,1']);
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $user->role_id = $request->role_id;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Rol actualizado correctamente.', 'user' => $user], 200);
    }

   public function getUsuarios()
    {
        try {
            $usuariosQuery = \App\Models\User::select('id', 'first_name', 'last_name', 'email', 'role_id', 'is_active', 'profile_picture', 'phone_number')->get();

            $usuarios = $usuariosQuery->map(function ($u) {
                $fotoUrl = $u->profile_picture ? (str_starts_with($u->profile_picture, 'http') ? $u->profile_picture : asset('storage/' . $u->profile_picture)) : null;
                return [
                    'id' => 'u_' . $u->id,
                    'first_name' => $u->first_name,
                    'last_name' => $u->last_name,
                    'email' => $u->email,
                    'role_id' => $u->role_id,
                    'is_active' => $u->is_active,
                    'profile_picture_url' => $fotoUrl,
                    'phone_number' => $u->phone_number,
                    'address' => 'No aplica',
                ];
            });

            $clientesQuery = DB::table('clients')
                ->select('id', 'user_id', 'name', 'email', 'phone', 'profile_picture', 'is_active')
                ->get();

            $clientes = $clientesQuery->map(function ($c) { 
                $fotoUrl = $c->profile_picture ? (str_starts_with($c->profile_picture, 'http') ? $c->profile_picture : asset('storage/' . $c->profile_picture)) : null;
                return [
                    'id' => 'c_' . $c->id, 
                    'first_name' => $c->name,
                    'last_name' => '',
                    'email' => $c->email,
                    'role_id' => 3,
                    'is_active' => $c->is_active,
                    'profile_picture_url' => $fotoUrl,
                    'phone_number' => $c->phone,
                    'address' => 'No registrada', 
                ];
            });

            $todosLosRegistros = $usuarios->concat($clientes);
            return response()->json($todosLosRegistros, 200);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function eliminarUsuario($id)
    {
        try {
            if (str_starts_with($id, 'c_')) {
                $realId = str_replace('c_', '', $id); 
                DB::table('clients')->where('id', $realId)->delete();
                return response()->json(['message' => 'Cliente eliminado correctamente'], 200);
            }

            $realId = str_replace('u_', '', $id);
            $usuario = \App\Models\User::find($realId);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            if ($usuario->role_id === 0) { 
                return response()->json(['error' => 'Acceso Denegado: No se puede eliminar al ROOT'], 403);
            }

            $usuario->delete();
            return response()->json(['message' => 'Usuario eliminado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    public function toggleBloqueo($id)
    {
        try {
            if (str_starts_with($id, 'c_')) {
                $realId = str_replace('c_', '', $id);
                $cliente = DB::table('clients')->where('id', $realId)->first();

                if (!$cliente) {
                    return response()->json(['error' => 'Cliente no encontrado'], 404);
                }

                $nuevoEstado = $cliente->is_active ? 0 : 1;
                DB::table('clients')->where('id', $realId)->update(['is_active' => $nuevoEstado]);

                $mensaje = $nuevoEstado ? 'Cliente desbloqueado con éxito' : 'Cliente bloqueado con éxito';
                return response()->json(['message' => $mensaje, 'is_active' => $nuevoEstado], 200);
            }

            $realId = str_replace('u_', '', $id);
            $usuario = \App\Models\User::find($realId);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            if ($usuario->role_id === 0) {
                return response()->json(['error' => 'Acceso Denegado.'], 403);
            }

            $usuario->is_active = !$usuario->is_active;
            $usuario->save();

            $mensaje = $usuario->is_active ? 'Usuario desbloqueado con éxito' : 'Usuario bloqueado con éxito';
            return response()->json(['message' => $mensaje, 'is_active' => $usuario->is_active], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cambiar estado: ' . $e->getMessage()], 500);
        }
    }

   public function getTecnicos() 
{
    $tecnicos = User::where('role_id', 2)->get(['id', 'first_name', 'last_name']);
    return response()->json($tecnicos);
}
}
