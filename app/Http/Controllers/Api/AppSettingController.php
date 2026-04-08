<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppSetting;

class AppSettingController extends Controller
{
    public function updateLoginBackground(Request $request)
    {
        $request->validate([
            'background_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('background_image')) {
            $file = $request->file('background_image');
            $filename = 'login_bg_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('backgrounds', $filename, 'public');
            $imageUrl = asset('storage/' . $path);

            $setting = AppSetting::updateOrCreate(
                ['setting_key' => 'login_background_image'], 
                ['setting_value' => $imageUrl]
            );

            return response()->json(['success' => true, 'data' => $setting], 200);
        }
        return response()->json(['success' => false, 'message' => 'No image found'], 400);
    }

    public function updateLoginColor(Request $request)
    {
        $request->validate([
            'color_hex' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        $setting = AppSetting::updateOrCreate(
            ['setting_key' => 'login_background_color'],
            ['setting_value' => $request->color_hex]
        );

        return response()->json([
            'success' => true,
            'message' => 'Color actualizado correctamente.',
            'data' => $setting
        ], 200);
    }
    public function deleteLoginBackground()
    {
        \App\Models\AppSetting::where('setting_key', 'login_background_image')->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.'
        ], 200);
    }

    public function getLoginSettings()
    {
        $imageSetting = AppSetting::where('setting_key', 'login_background_image')->first();
        $colorSetting = AppSetting::where('setting_key', 'login_background_color')->first();

        return response()->json([
            'success' => true,
            'settings' => [
                'imageUrl' => $imageSetting ? $imageSetting->setting_value : null,
                'colorHex' => $colorSetting ? $colorSetting->setting_value : '#000000' 
            ]
        ], 200);
    }
}