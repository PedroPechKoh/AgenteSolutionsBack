<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function getServices($id)
    {
        $servicios = Service::where('assigned_to', $id)
                            ->orderBy('created_at', 'desc')
                            ->get();
                            
        return response()->json($servicios);
    }

   public function getServicesByProperty($idTecnico, $idPropiedad)
    {
        $servicios = Service::with('property.client')
                            ->where('assigned_to', $idTecnico)
                            ->where('property_id', $idPropiedad)
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json($servicios);
    }
    public function getServiceDetalle($id)
    {
        $servicio = Service::with('property.client')->find($id);
        
        return response()->json($servicio);
    }
}