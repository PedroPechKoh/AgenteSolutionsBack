<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChecklistTemplate;

class ChecklistTemplateController extends Controller
{
    public function index()
    {
        $templates = ChecklistTemplate::orderBy('name')->get();
        return response()->json($templates, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|array',
        ]);

        $template = ChecklistTemplate::create([
            'name' => $request->input('name'),
            'content' => $request->input('content'),
        ]);

        return response()->json(['message' => 'Plantilla guardada', 'template' => $template], 201);
    }
}
