<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobQuote;
use App\Models\JobRequest;

class JobQuoteController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate(['price' => 'required|numeric']);
        $user = auth('sanctum')->user();
        
        $jobReq = JobRequest::find($id);
        if (!$jobReq || $jobReq->tenant_id !== $user->tenant_id || $jobReq->status !== 'open') {
            return response()->json(['error' => 'Solicitud no disponible.'], 400);
        }

        $existing = JobQuote::where('job_request_id', $id)->where('technician_id', $user->id)->first();
        if ($existing) {
            return response()->json(['error' => 'Ya cotizaste esta solicitud.'], 400);
        }

        $quote = JobQuote::create([
            'job_request_id' => $id,
            'technician_id' => $user->id,
            'price' => $request->price,
            'estimated_days' => $request->estimated_days,
            'message' => $request->message,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Cotización enviada.', 'data' => $quote], 201);
    }

    public function myQuotes(Request $request)
    {
        $user = auth('sanctum')->user();
        $quotes = JobQuote::where('technician_id', $user->id)
            ->with(['jobRequest.client', 'jobRequest.property'])
            ->get();
        return response()->json($quotes);
    }
}
