<?php

namespace Domain\Farming\Actions;

use Domain\Farming\DTOs\CreatePlotDTO;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\DB;

class CreatePlotAction
{
    public function __invoke(CreatePlotDTO $dto): Plot
    {
        // Convert coordinates array to WKT (Well-Known Text) for PostGIS
        // Coordinates format expected from frontend Leaflet: [[lng, lat], [lng, lat], ...]
        // Note: PostGIS expects the first and last point of a polygon to be identical to close it.
        $coords = $dto->coordinates;
        if (count($coords) > 0 && $coords[0] !== end($coords)) {
            $coords[] = $coords[0]; // Close the polygon
        }

        $points = array_map(function ($point) {
            // Strict float casting ensures no malicious SQL characters can be injected via the WKT string
            return (float) $point[0].' '.(float) $point[1];
        }, $coords);

        $wkt = 'POLYGON(('.implode(', ', $points).'))';

        // We can safely use DB::raw here because $wkt is guaranteed to only contain floats and safe WKT formatting
        $plotId = DB::table('plots')->insertGetId([
            'farm_id' => $dto->farmId,
            'name' => $dto->name,
            'soil_type' => $dto->soilType,
            'polygon' => DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
            'calculated_area' => DB::raw("(ST_Area(ST_GeomFromText('{$wkt}', 4326)::geography) / 10000)"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Plot::findOrFail($plotId);
    }
}
