<?php

namespace App\Farming\Controllers;

use App\Http\Controllers\Controller;
use Domain\Farming\Models\RestrictedZone;
use Illuminate\Http\JsonResponse;

class RestrictedZoneController extends Controller
{
    /**
     * Return all restricted zones as a GeoJSON FeatureCollection.
     * Used by the frontend map to render no-plot zones in red.
     */
    public function index(): JsonResponse
    {
        $zones = RestrictedZone::selectRaw('id, name, type, ST_AsGeoJSON(polygon) as geojson')->get();

        $features = $zones->map(function ($zone) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($zone->geojson),
                'properties' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'type' => $zone->type,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
