<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // <-- IMPORTANTE: Añadimos esto para poder escribir en la tabla clients

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
            // Puedes agregar phone_number si también lo mandas desde el frontend
            'phone_number' => 'nullable|string|max:20'
        ]);

        // 2. Guardamos usando los campos correctos de la tabla users
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone_number' => $request->phone_number ?? null,
            'is_active' => 1
        ]);

        // 👇 LA MAGIA PARA MATAR A LOS USUARIOS FANTASMA 👇
        // 3. Si el rol es 3 (Cliente), le creamos su perfil automáticamente en la tabla clients
        if ($user->role_id == 3) {
            DB::table('clients')->insert([
                'user_id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name, // Concatenamos el nombre para el perfil
                'email' => $user->email,
                'phone' => $user->phone_number ?? 'Sin teléfono',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        // 👆 FIN DE LA MAGIA 👆

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

        $token = $user->createToken('AgenteToken')->plainTextToken;

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
}