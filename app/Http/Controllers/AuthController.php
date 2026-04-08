<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registro(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer' // Quitamos el 'in:0,1,2' para permitir el rol 3 (Clientes)
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), 
            'role_id' => $request->role_id,
            'is_active' => 1 
        ]);

        // Generamos el token inmediatamente después del registro
        $token = $user->createToken('AgenteToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
            'token' => $token // Enviamos el token a React
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas. Verifica tu correo y contraseña.'
            ], 401);
        }

        if ($user->is_active == 0) {
            return response()->json([
                'error' => 'No puedes acceder a tu cuenta, por favor contactate con el servicio de soporte.'
            ], 403);
        }

        // --- ¡AQUÍ ESTÁ LA MAGIA! ---
        // Creamos un token único para esta sesión
        $token = $user->createToken('AgenteToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => $user,
            'token' => $token // Esta es la llave que React debe guardar
        ], 200);
    }

    // Método opcional para cerrar sesión y destruir el token
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }
}