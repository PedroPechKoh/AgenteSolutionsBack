<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobRequest;
use App\Models\JobQuote;
use App\Models\WorkOrder;
use App\Models\Service;
use Carbon\Carbon;

class JobRequestController extends Controller
{
    public function availableForTechnician(Request $request)
    {
        $user = auth('sanctum')->user();
        $requests = JobRequest::where('tenant_id', $user->tenant_id)
            ->where('status', 'open')
            ->with(['client', 'property', 'specialty'])
            ->get();
        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string'
        ]);

        $user = auth('sanctum')->user();
        
        $jobReq = JobRequest::create([
            'contratista_user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'client_id' => $request->client_id,
            'property_id' => $request->property_id,
            'title' => $request->title,
            'description' => $request->description,
            'specialty_id' => $request->specialty_id,
            'status' => 'open'
        ]);

        return response()->json(['message' => 'Solicitud publicada.', 'data' => $jobReq], 201);
    }

    public function myRequests(Request $request)
    {
        $user = auth('sanctum')->user();
        $requests = JobRequest::where('contratista_user_id', $user->id)
            ->with(['client', 'property', 'specialty'])
            ->get();
        return response()->json($requests);
    }

    public function show($id)
    {
        $user = auth('sanctum')->user();
        $jobReq = JobRequest::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->with(['client', 'property', 'specialty', 'quotes.technician'])
            ->first();
            
        if (!$jobReq) {
            return response()->json(['error' => 'No encontrado'], 404);
        }
        return response()->json($jobReq);
    }

    public function selectQuote(Request $request, $id)
    {
        $request->validate(['quote_id' => 'required|integer']);
        $user = auth('sanctum')->user();

        $jobReq = JobRequest::where('id', $id)->where('contratista_user_id', $user->id)->first();
        if (!$jobReq) return response()->json(['error' => 'No encontrado'], 404);

        $quote = JobQuote::where('id', $request->quote_id)->where('job_request_id', $id)->first();
        if (!$quote) return response()->json(['error' => 'Cotización no válida.'], 404);

        $jobReq->status = 'assigned';
        $jobReq->selected_quote_id = $quote->id;
        $jobReq->save();

        $quote->status = 'accepted';
        $quote->save();
        
        // Reject others
        JobQuote::where('job_request_id', $id)->where('id', '!=', $quote->id)->update(['status' => 'rejected']);

        // A work order could be generated here in future logic.

        return response()->json(['message' => 'Cotización seleccionada.', 'data' => $jobReq]);
    }
}
