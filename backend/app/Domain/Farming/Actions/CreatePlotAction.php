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
            return $point[0] . ' ' . $point[1];
        }, $coords);
        
        $wkt = "POLYGON((" . implode(', ', $points) . "))";

        // Insert using DB statement to utilize PostGIS functions for Geometry and Area
        // ST_GeomFromText creates the geometry.
        // ST_Area calculates the area in square meters (if cast to geography).
        
        $plotId = DB::table('plots')->insertGetId([
            'farm_id' => $dto->farmId,
            'name' => $dto->name,
            'soil_type' => $dto->soilType,
            'polygon' => DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
            // Calculate area in hectares (1 hectare = 10,000 square meters)
            'calculated_area' => DB::raw("(ST_Area(ST_GeomFromText('{$wkt}', 4326)::geography) / 10000)"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Plot::findOrFail($plotId);
    }
}
