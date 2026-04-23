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
use App\Http\Controllers\PropertyCategoryController;
use App\Http\Controllers\NotificationController;

// ========================================================
// 🟢 ZONA PÚBLICA (Sin Token - Cualquiera puede entrar)
// ========================================================

Route::post('/login', [AuthController::class, 'login']);

// Registro Privado (Exclusivo para el Admin)
Route::post('/registro-usuario', [AuthController::class, 'registro']);

// Registro Público (Exclusivo para Clientes)
Route::post('/registro-cliente', function (\Illuminate\Http\Request $request) {
    try {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // 1. Creamos el usuario para el Login
            $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                'role_id' => 3, // Rol Cliente
                'first_name' => trim($request->first_name),
                'last_name' => trim($request->last_name),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Insertamos en clientes juntando el nombre para esa tabla
            \Illuminate\Support\Facades\DB::table('clients')->insert([
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => $request->email,
                'phone' => $request->phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => '¡Registro exitoso, Pedro!'], 201);
        });
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Rutas para Personalizar Login
Route::post('/ui/settings/login-background/image', [AppSettingController::class, 'updateLoginBackground']);
Route::delete('/ui/settings/login-background/image', [AppSettingController::class, 'deleteLoginBackground']);
Route::post('/ui/settings/login-background/color', [AppSettingController::class, 'updateLoginColor']);
Route::get('/ui/settings/login-settings', [AppSettingController::class, 'getLoginSettings']);

// Limpiar caché de Railway
Route::get('/limpiar-cache', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return response()->json(['message' => '¡Memoria de Railway reseteada con éxito!']);
});


// ========================================================
// 🔴 ZONA SEGURA (Solo entras si traes el Token de Sanctum)
// ========================================================

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // --- USUARIOS Y PERFILES ---
    Route::get('/usuarios', [UserController::class, 'getUsuarios']);
    Route::delete('/usuarios/{id}', [UserController::class, 'eliminarUsuario']);
    Route::put('/usuarios/{id}/toggle-bloqueo', [UserController::class, 'toggleBloqueo']);
    Route::post('/usuarios/update-profile', [UserController::class, 'updateProfile']);
    Route::put('/usuarios/{id}/rol', [UserController::class, 'updateRole']);
    Route::get('/usuarios/tecnicos', [UserController::class, 'getTecnicos']);

    Route::post('/upload-profile-picture', [ImageController::class, 'uploadProfilePicture']);
    Route::post('/update-photos', function (\Illuminate\Http\Request $request) {
        $user = User::find($request->user_id);
        if (!$user) return response()->json(['error' => 'User not found'], 404);

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

    // --- PROPIEDADES Y MAPAS ---
    Route::get('/propiedades', [PropertyController::class, 'index']);
    Route::post('/registro-propiedad', [PropertyController::class, 'store']);
    Route::delete('/propiedades/{id}', [PropertyController::class, 'destroy']);
    Route::get('/propiedades/{id}/dashboard', [PropertyController::class, 'getDashboardData']);

    Route::get('/map', function () {
        $propiedades = \Illuminate\Support\Facades\DB::table('properties')
            ->leftJoin('clients', 'properties.client_id', '=', 'clients.id')
            ->whereNotNull('properties.coordinates')
            ->where('properties.coordinates', '!=', '')
            ->select('properties.id as prop_id', 'properties.address', 'properties.coordinates', 'clients.name', 'clients.phone', 'clients.profile_picture')
            ->get();

        $marcadoresBrutos = $propiedades->map(function ($prop) {
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
        });

        // ✅ REPARADO: Filtramos sin usar la notación de flecha que confundía a Laravel
        $marcadoresLimpios = [];
        foreach ($marcadoresBrutos as $m) {
            if ($m['lat'] !== null && $m['lng'] !== null) {
                $marcadoresLimpios[] = $m;
            }
        }

        return response()->json($marcadoresLimpios);
    });

    // --- SERVICIOS Y LEVANTAMIENTOS ---
    Route::get('/servicios', [ServiceController::class, 'index']);
    Route::post('/servicios', [ServiceController::class, 'store']);
    Route::get('/servicios/{id}', [ServiceController::class, 'show']);
    Route::put('/servicios/{id}', [ServiceController::class, 'update']);
    Route::put('/servicios/{id}/asignar', [ServiceController::class, 'assignTechnician']);
    Route::post('/services/assign', [ServiceController::class, 'store']);

    Route::put('/servicios/{id}/confirmar-cliente', [ServiceController::class, 'confirmarCitaCliente']);
    Route::put('/servicios/{id}/solicitar-reprogramacion', [ServiceController::class, 'solicitarReprogramacion']);

    Route::get('/tecnico/{id}/servicios', [ServiceController::class, 'getTecnicoServicios']);
    Route::get('/tecnico/{idTecnico}/propiedad/{idPropiedad}/servicios', [ServiceController::class, 'getServicesByProperty']);

    Route::post('/propiedades/servicios', [PropertyController::class, 'storeWorkOrder']);
    Route::get('/propiedades/{id}/work-orders', [PropertyController::class, 'getWorkOrders']);
    Route::put('/work-orders/{id}/status', [PropertyController::class, 'updateWorkOrderStatus']);

    // --- GESTIÓN DE ZONAS (NIVEL 3 y 4) ---
    Route::post('/property-areas', [PropertyAreaController::class, 'store']);
    Route::get('/property-areas', [PropertyAreaController::class, 'index']);
    Route::get('/properties/{id}/areas', [PropertyAreaController::class, 'getByProperty']);
    Route::put('/property-areas/{id}', [PropertyAreaController::class, 'update']);
    Route::delete('/property-areas/{id}', [PropertyAreaController::class, 'destroy']);
    Route::get('/areas/{id}/subareas', [PropertyAreaController::class, 'getSubAreas']);

    // --- CATEGORÍAS Y COMPONENTES (NIVEL 5) ---
    Route::get('/areas/{id}/categories', [PropertyCategoryController::class, 'getByArea']);
    Route::post('/property-categories', [PropertyCategoryController::class, 'store']);

    Route::get('/areas/{id}/components', [PropertyComponentController::class, 'getByArea']);
    Route::post('/property-components', [PropertyComponentController::class, 'store']);
    Route::delete('/property-components/{id}', [PropertyComponentController::class, 'destroy']);
    Route::put('/property-components/{id}', [PropertyComponentController::class, 'update']);
    Route::get('/properties/{propertyId}/components', [PropertyComponentController::class, 'getComponentsByProperty']);

    // --- INVENTARIOS GLOBALES ---
    Route::get('/appliances', [ApplianceController::class, 'index']);
    Route::post('/appliances', [ApplianceController::class, 'store']);
    Route::get('/catalog/summary', [PropertyComponentController::class, 'getCatalogSummary']);
    Route::get('/catalog/details', [PropertyComponentController::class, 'getCatalogDetails']);

    // --- COTIZACIONES ---
    Route::get('/cotizaciones', [QuoteController::class, 'index']);
    Route::post('/cotizaciones', [QuoteController::class, 'store']);
    Route::put('/cotizaciones/{id}/status', [QuoteController::class, 'updateStatus']);
    Route::post('/servicios/{id}/confirmar-materiales', [QuoteController::class, 'confirmMaterials']);

    // --- NOTIFICACIONES ---
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/all', [NotificationController::class, 'getAll']);

    ///Cotizaciones
    Route::put('/cotizaciones/{id}/observaciones', [QuoteController::class, 'updateObservations']);
    Route::post('/cotizaciones/{id}/finalizar', [QuoteController::class, 'finalizarCotizacion']);
});