<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;

class ImageController extends Controller
{
    public function uploadProfilePicture(Request $request)
    {
        // 1. Validamos que el archivo sea una imagen real y pese menos de 2MB
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            // 2. Subimos la imagen a la nube
            $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => 'agente_perfiles'
            ])->getSecurePath();

            // 3. Devolvemos la URL segura a React
            return response()->json([
                'message' => '¡Imagen subida con éxito!',
                'url' => $uploadedFileUrl
            ], 200);

        } catch (\Exception $e) {
            // Ahora Laravel nos dirá el chisme completo
            return response()->json([
                'error' => 'Error interno al subir: ' . $e->getMessage(),
                'archivo_fallido' => $e->getFile(),
                'linea_exacta' => $e->getLine()
            ], 500);
        }
    }
}