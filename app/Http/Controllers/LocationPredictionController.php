<?php

namespace App\Http\Controllers;

use App\Services\LocationPredictionService;
use Illuminate\Http\Request;

class LocationPredictionController extends Controller
{
    public function index(Request $request, LocationPredictionService $locationPredictionService)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return response()->json([
            'data' => $locationPredictionService->search($validated['q']),
        ]);
    }
}
