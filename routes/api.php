<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplianceController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::post('/login-rapido', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    return response()->json([
        'message' => 'Login exitoso',
        'user_name' => $user->name,
        'role_id' => $user->role_id // Aquí mandamos el 0 o 1
    ]);
});

Route::post('/appliances', [ApplianceController::class, 'store']);
Route::get('/appliances', [ApplianceController::class, 'index']);