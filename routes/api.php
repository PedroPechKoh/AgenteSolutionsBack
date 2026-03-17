<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplianceController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;

Route::post('/login-rapido', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

   return response()->json([
        'message' => 'Login exitoso',
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'phone_number' => $user->phone_number,
        'birth_date' => $user->birth_date,
        'role_id' => $user->role_id,
        'profile_picture' => $user->profile_picture,
        'cover_picture' => $user->cover_picture,
        'created_at' => $user->created_at
    ]);
});

Route::post('/update-photos', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::find($request->user_id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    if ($request->hasFile('profile_picture')) {
        $profilePath = $request->file('profile_picture')->store('profiles', 'public');
        $user->profile_picture = asset('storage/' . $profilePath);
    }

    if ($request->hasFile('cover_picture')) {
        $coverPath = $request->file('cover_picture')->store('covers', 'public');
        $user->cover_picture = asset('storage/' . $coverPath);
    }
    $user->save();

    return response()->json([
        'message' => 'Photos updated successfully',
        'profile_picture' => $user->profile_picture,
        'cover_picture' => $user->cover_picture
    ]);
});

Route::post('/update-profile', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::find($request->user_id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    $user->first_name = $request->first_name;
    $user->last_name = $request->last_name;
    $user->phone_number = $request->phone_number;
    $user->email = $request->email;
    $user->birth_date = $request->birth_date;

    $user->save();

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => $user
    ]);
});

Route::get('/map', function () {
    $propiedades = \Illuminate\Support\Facades\DB::table('properties')
        ->leftJoin('clients', 'properties.client_id', '=', 'clients.id')
        ->whereNotNull('properties.coordinates')
        ->where('properties.coordinates', '!=', '')
        ->select(
            'properties.id as prop_id',
            'properties.address',
            'properties.coordinates',
            'clients.name', 
            'clients.phone',
            'clients.profile_picture'
        )
        ->get();

    $marcadores = $propiedades->map(function ($prop) {
        $partes = explode(',', $prop->coordinates);
        
        return [
            'id' => $prop->prop_id,
            'address' => $prop->address,
            'lat' => isset($partes[0]) ? (float) trim($partes[0]) : null,
            'lng' => isset($partes[1]) ? (float) trim($partes[1]) : null,
            
            
            'owner_name' => $prop->name, 
            'phone' => $prop->phone,
            'picture' => $prop->profile_picture 
        ];
    })->filter(function($m) {
        return $m['lat'] !== null && $m['lng'] !== null;
    })->values();

    return response()->json($marcadores);
});

Route::post('/public-client-register', function (Request $request) {
    
    return DB::transaction(function () use ($request) {
        
        $userId = DB::table('users')->insertGetId([
            'role_id' => 3,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone,
            'password' => Hash::make($request->password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nombreCompleto = trim($request->first_name . ' ' . $request->last_name);

        DB::table('clients')->insert([
            'user_id' => $userId, 
            'name' => $nombreCompleto,
            'email' => $request->email,
            'phone' => $request->phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Cliente y Usuario creados con éxito']);
    });
});

Route::post('/appliances', [ApplianceController::class, 'store']);
Route::get('/appliances', [ApplianceController::class, 'index']);
Route::post('/registro-usuario', [AuthController::class, 'registro']);
Route::post('/registro-propiedad', [PropertyController::class, 'store']);
Route::put('/usuarios/{id}/rol', [UserController::class, 'updateRole']);
Route::get('/tecnico/{id}/servicios', [ServiceController::class, 'getServices']);
Route::get('/tecnico/{idTecnico}/propiedad/{idPropiedad}/servicios', 
[App\Http\Controllers\ServiceController::class, 'getServicesByProperty']);
Route::get('/servicios/{id}', [ServiceController::class, 'getServiceDetalle']);