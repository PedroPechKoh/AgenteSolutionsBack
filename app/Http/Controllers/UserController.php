<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; 

class UserController extends Controller
{
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role_id' => 'required|integer|in:0,1' 
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->role_id = $request->role_id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'El rol del usuario se actualizó correctamente.',
            'user' => $user
        ], 200);
    }
}