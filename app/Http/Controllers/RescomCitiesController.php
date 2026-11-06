<?php

namespace App\Http\Controllers;

use App\Support\RescomReferenceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RescomCitiesController extends Controller
{
    public function __invoke(Request $request, RescomReferenceData $rescomReferenceData): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json($rescomReferenceData->searchCities($validated['q']));
    }
}
