<?php

namespace App\Farming\Controllers;

use App\Farming\Requests\StorePlotRequest;
use App\Farming\Resources\PlotResource;
use App\Http\Controllers\Controller;
use Domain\Farming\Actions\CreatePlotAction;
use Domain\Farming\DTOs\CreatePlotDTO;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Illuminate\Http\JsonResponse;

class PlotController extends Controller
{
    public function index(Farm $farm): JsonResponse
    {
        // Simple authorization check inline (or use Policy)
        if ($farm->user_id !== request()->user()->id) {
            abort(403);
        }

        // Return GeoJSON format for the map
        $plots = Plot::where('farm_id', $farm->id)
            ->selectRaw('id, name, soil_type, calculated_area, ST_AsGeoJSON(polygon) as geojson, created_at, updated_at')
            ->get();

        $features = $plots->map(function ($plot) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($plot->geojson),
                'properties' => [
                    'id' => $plot->id,
                    'name' => $plot->name,
                    'soil_type' => $plot->soil_type?->value,
                    'calculated_area' => $plot->calculated_area,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function store(StorePlotRequest $request, Farm $farm, CreatePlotAction $action): JsonResponse
    {
        if ($farm->user_id !== $request->user()->id) {
            abort(403);
        }

        $dto = CreatePlotDTO::fromRequest($request->validated(), $farm->id);
        $plot = $action($dto);

        return response()->json([
            'message' => 'Plot created successfully',
            'data' => new PlotResource($plot),
        ], 201);
    }
}
