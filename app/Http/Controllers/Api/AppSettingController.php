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
            $cloudinary = new \Cloudinary\Cloudinary('cloudinary://942191234587844:VmNYB6w4vj3DdLqI9SZSKVofOi0@dcj5rcpi8');
            $respuestaNube = $cloudinary->uploadApi()->upload($request->file('background_image')->getRealPath(), [
                'folder' => 'login_backgrounds'
            ]);
            $imageUrl = $respuestaNube['secure_url'];

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
        $logoSetting = AppSetting::where('setting_key', 'app_logo')->first();

        return response()->json([
            'success' => true,
            'settings' => [
                'imageUrl' => $imageSetting ? $imageSetting->setting_value : null,
                'colorHex' => $colorSetting ? $colorSetting->setting_value : '#000000',
                'appLogo' => $logoSetting ? $logoSetting->setting_value : null
            ]
        ], 200);
    }

    public function updateAppLogo(Request $request)
    {
        $request->validate([
            'app_logo' => 'required|file|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ]);

        if ($request->hasFile('app_logo')) {
            $cloudinary = new \Cloudinary\Cloudinary('cloudinary://942191234587844:VmNYB6w4vj3DdLqI9SZSKVofOi0@dcj5rcpi8');
            $respuestaNube = $cloudinary->uploadApi()->upload($request->file('app_logo')->getRealPath(), [
                'folder' => 'app_logos'
            ]);
            $imageUrl = $respuestaNube['secure_url'];

            $setting = AppSetting::updateOrCreate(
                ['setting_key' => 'app_logo'], 
                ['setting_value' => $imageUrl]
            );

            return response()->json(['success' => true, 'data' => $setting], 200);
        }
        return response()->json(['success' => false, 'message' => 'No image found'], 400);
    }

    public function deleteAppLogo()
    {
        \App\Models\AppSetting::where('setting_key', 'app_logo')->delete();
        return response()->json(['success' => true, 'message' => 'Logo eliminado correctamente.'], 200);
    }

    public function updateSidebarLinks(Request $request)
    {
        $request->validate([
            'links' => 'present|array',
        ]);

        $setting = AppSetting::updateOrCreate(
            ['setting_key' => 'sidebar_client_links'],
            ['setting_value' => json_encode($request->links)]
        );

        return response()->json(['success' => true, 'data' => $setting], 200);
    }

    public function getSidebarLinks()
    {
        $setting = AppSetting::where('setting_key', 'sidebar_client_links')->first();
        $links = $setting ? json_decode($setting->setting_value, true) : [];
        return response()->json(['success' => true, 'links' => $links], 200);
    }
}