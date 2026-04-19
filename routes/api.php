<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplianceController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\AppSettingController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\PropertyComponentController;
use App\Http\Controllers\PropertyAreaController;

// ========================================================
// 🟢 ZONA PÚBLICA (Sin Token - Cualquiera puede entrar)
// ========================================================

// 1. Logins y Registros
Route::post('/login', [AuthController::class, 'login']);
Route::post('/registro-usuario', [AuthController::class, 'registro']);

// Mantuve tu registro de clientes aquí para que siga funcionando
Route::post('/public-client-register', function (Illuminate\Http\Request $request) {
    try {
        return DB::transaction(function () use ($request) {
            // 1. Creamos el usuario (Ajustado a tus columnas de users)
            $userId = DB::table('users')->insertGetId([
                'role_id' => 3,
                'name' => trim($request->name), // Enviamos el nombre completo
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Insertamos en clientes (SIN user_id porque tu tabla no lo tiene)
            DB::table('clients')->insert([
                'name' => trim($request->name),
                'email' => $request->email,
                'phone' => $request->phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => '¡Registro exitoso, Pedro!']);
        });
    } catch (\Exception $e) {
        // Esto te dirá el error real si algo más falla
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Rutas para Personalizar Login (Es normal que sean públicas para que se vean antes de entrar)
Route::post('/ui/settings/login-background/image', [AppSettingController::class, 'updateLoginBackground']);
Route::delete('/ui/settings/login-background/image', [AppSettingController::class, 'deleteLoginBackground']);
Route::post('/ui/settings/login-background/color', [AppSettingController::class, 'updateLoginColor']);
Route::get('/ui/settings/login-settings', [AppSettingController::class, 'getLoginSettings']);


// ========================================================
// 🔴 ZONA SEGURA (Solo entras si traes el Token de Sanctum)
// ========================================================

Route::middleware('auth:sanctum')->group(function () {

    // --- USUARIOS Y PERFILES ---
    Route::get('/usuarios', [UserController::class, 'getUsuarios']);
    Route::delete('/usuarios/{id}', [UserController::class, 'eliminarUsuario']);
    Route::put('/usuarios/{id}/toggle-bloqueo', [UserController::class, 'toggleBloqueo']);
    Route::post('/usuarios/update-profile', [UserController::class, 'updateProfile']);
    Route::put('/usuarios/{id}/rol', [UserController::class, 'updateRole']);
    Route::get('/usuarios/tecnicos', [UserController::class, 'getTecnicos']);

    // Ruta para subir fotos a Cloudinary
Route::post('/upload-profile-picture', [ImageController::class, 'uploadProfilePicture']);



    // --- FOTOS DE PERFIL ---
    Route::post('/update-photos', function (\Illuminate\Http\Request $request) {
        $user = \App\Models\User::find($request->user_id);
        if (!$user)
            return response()->json(['error' => 'User not found'], 404);

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

    // --- PROPIEDADES ---
    Route::post('/registro-propiedad', [PropertyController::class, 'store']);
    Route::delete('/propiedades/{id}', [PropertyController::class, 'destroy']);
    Route::get('/map', function () {
        // Tu código de mapa original...
        $propiedades = \Illuminate\Support\Facades\DB::table('properties')
            ->leftJoin('clients', 'properties.client_id', '=', 'clients.id')
            ->whereNotNull('properties.coordinates')
            ->where('properties.coordinates', '!=', '')
            ->select('properties.id as prop_id', 'properties.address', 'properties.coordinates', 'clients.name', 'clients.phone', 'clients.profile_picture')
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
        })->filter(function ($m) {
            return $m['lat'] !== null && $m['lng'] !== null;
        })->values();

        return response()->json($marcadores);
    });

    // --- SERVICIOS Y LEVANTAMIENTOS ---
    Route::get('/servicios', [ServiceController::class, 'index']);
    Route::post('/servicios', [ServiceController::class, 'store']);
    Route::get('/servicios/{id}', [App\Http\Controllers\ServiceController::class, 'show']);
    Route::put('/servicios/{id}/asignar', [ServiceController::class, 'assignTechnician']);
    Route::post('/services/assign', [App\Http\Controllers\ServiceController::class, 'store']);

    Route::get('/tecnico/{id}/servicios', [ServiceController::class, 'getServices']);
    Route::get('/tecnico/{idTecnico}/propiedad/{idPropiedad}/servicios', [App\Http\Controllers\ServiceController::class, 'getServicesByProperty']);

    // --- COTIZACIONES ---
    Route::get('/cotizaciones', [QuoteController::class, 'index']);
    Route::post('/cotizaciones', [QuoteController::class, 'store']);

    // --- EQUIPOS Y COMPONENTES ---
    Route::get('/appliances', [ApplianceController::class, 'index']);
    Route::post('/appliances', [ApplianceController::class, 'store']);
    Route::get('/catalog/summary', [PropertyComponentController::class, 'getCatalogSummary']);
    Route::get('/catalog/details', [PropertyComponentController::class, 'getCatalogDetails']);
    Route::get('/properties/{propertyId}/components', [App\Http\Controllers\PropertyComponentController::class, 'getComponentsByProperty']);

    //Notificaciones
    Route::get('/notifications/unread', [App\Http\Controllers\NotificationController::class, 'getUnread']);
    Route::put('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::get('/notifications/all', [App\Http\Controllers\NotificationController::class, 'getAll']);

    ///Rutas para reagendar levantamiento
    Route::put('/servicios/{id}/confirmar-cliente', [App\Http\Controllers\ServiceController::class, 'confirmarCitaCliente']);
    Route::put('/servicios/{id}/solicitar-reprogramacion', [App\Http\Controllers\ServiceController::class, 'solicitarReprogramacion']);
});

//Tecnico
Route::get('/tecnico/{id}/servicios', [ServiceController::class, 'getTecnicoServicios']);
//Registro de Areas
Route::post('/property-areas', [PropertyAreaController::class, 'store']);
Route::get('/property-areas', [PropertyAreaController::class, 'index']);
Route::get('/properties/{id}/areas', [PropertyAreaController::class, 'getByProperty']);
Route::put('/property-areas/{id}', [PropertyAreaController::class, 'update']);
Route::delete('/property-areas/{id}', [PropertyAreaController::class, 'destroy']);
Route::get('/areas/{id}/components', [PropertyComponentController::class, 'getByArea']);
Route::post('/property-components', [PropertyComponentController::class, 'store']);
Route::get('/areas/{id}/subareas', [App\Http\Controllers\PropertyAreaController::class, 'getSubAreas']);
Route::get('/areas/{id}/categories', [App\Http\Controllers\PropertyCategoryController::class, 'getByArea']);
Route::post('/property-categories', [App\Http\Controllers\PropertyCategoryController::class, 'store']);
Route::delete('/property-components/{id}', [App\Http\Controllers\PropertyComponentController::class, 'destroy']);
Route::put('/property-components/{id}', [App\Http\Controllers\PropertyComponentController::class, 'update']);
Route::get('/propiedades/{id}/dashboard', [PropertyController::class, 'getDashboardData']);
Route::post('/propiedades/servicios', [PropertyController::class, 'storeWorkOrder']);
Route::get('/propiedades/{id}/work-orders', [PropertyController::class, 'getWorkOrders']);
Route::put('/work-orders/{id}/status', [PropertyController::class, 'updateWorkOrderStatus']);
Route::get('/propiedades', [PropertyController::class, 'index']);

//Subir imagenes
use App\Http\Controllers\ImageController;


Route::get('/limpiar-cache', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return response()->json(['message' => '¡Memoria de Railway reseteada con éxito!']);
});