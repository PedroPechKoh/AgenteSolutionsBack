<?php

namespace App\Http\Controllers;

use App\Models\Appliance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplianceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required',
            'type' => 'required',
            'brand' => 'required',
            'model' => 'required',
        ]);

        try {
            $data = $request->all();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('appliances', 'public');
                $data['image_path'] = $path;
            }

            $appliance = Appliance::create($data);

            return response()->json([
                'message' => 'Éxito',
                'data' => $appliance
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error al guardar: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function index()
    {
        return Appliance::orderBy('created_at', 'desc')->get();
    }

}






