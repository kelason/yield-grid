<?php

namespace Domain\Farming\Actions;

use Domain\Farming\DTOs\CreatePlotDTO;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePlotAction
{
    private const LONGITUDE_INDEX = 0;

    private const LATITUDE_INDEX = 1;

    private const FIRST_ITERATION_INDEX = 0;

    public function __invoke(CreatePlotDTO $dto): Plot
    {
        // Convert coordinates array to WKT (Well-Known Text) for PostGIS
        // Coordinates format expected from frontend Leaflet: [[lng, lat], [lng, lat], ...]
        // Note: PostGIS expects the first and last point of a polygon to be identical to close it.
        $coords = $dto->coordinates;
        if (count($coords) > self::FIRST_ITERATION_INDEX && $coords[self::FIRST_ITERATION_INDEX] !== end($coords)) {
            $coords[] = $coords[self::FIRST_ITERATION_INDEX]; // Close the polygon
        }

        $points = array_map(function ($point) {
            // Strict float casting ensures no malicious SQL characters can be injected via the WKT string
            return (float) $point[self::LONGITUDE_INDEX].' '.(float) $point[self::LATITUDE_INDEX];
        }, $coords);

        $wkt = 'POLYGON(('.implode(', ', $points).'))';

        // Check if the drawn polygon intersects any restricted zone (houses, roads, rivers, etc.)
        $conflicting = DB::table('restricted_zones')
            ->whereRaw('ST_Intersects(polygon, ST_GeomFromText(?, 4326))', [$wkt])
            ->first();

        if ($conflicting) {
            $typeName = ucfirst($conflicting->type ?? 'restricted zone');
            throw ValidationException::withMessages([
                'coordinates' => "Your plot cannot overlap a {$typeName} (\"{$conflicting->name}\"). Please draw only on vacant, agricultural land.",
            ]);
        }

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
