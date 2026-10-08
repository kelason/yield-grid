<?php

declare(strict_types=1);

namespace App\Farming\Controllers;

use App\Constants\FloodRiskConstants;
use App\Farming\Requests\FloodZoneIndexRequest;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FloodHazardZoneController extends Controller
{
    /**
     * Return flood hazard zones intersecting a bbox as a GeoJSON FeatureCollection.
     * Used by the frontend map to render the flood overlay.
     */
    public function index(FloodZoneIndexRequest $request): JsonResponse
    {
        [$minx, $miny, $maxx, $maxy] = $request->bbox();

        $zones = DB::select(
            'SELECT id, hazard_class, ST_AsGeoJSON(ST_SimplifyPreserveTopology(polygon, ?)) AS geojson FROM flood_hazard_zones WHERE ST_Intersects(polygon, ST_MakeEnvelope(?, ?, ?, ?, 4326)) ORDER BY id ASC LIMIT ?',
            [FloodRiskConstants::ZONE_SIMPLIFY_TOLERANCE, $minx, $miny, $maxx, $maxy, FloodRiskConstants::MAX_ZONES_PER_RESPONSE]
        );

        $features = array_map(fn ($zone): array => [
            'type' => 'Feature',
            'geometry' => json_decode($zone->geojson),
            'properties' => [
                'id' => $zone->id,
                'hazard_class' => $zone->hazard_class,
            ],
        ], $zones);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
