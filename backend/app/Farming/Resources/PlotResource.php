<?php

namespace App\Farming\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class PlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Extract GeoJSON from the PostGIS polygon if it was loaded
        $geoJson = null;
        if ($this->polygon) {
            // Note: Since polygon is binary natively, we often need to fetch it via ST_AsGeoJSON in the query.
            // For this resource, if it's already a string/binary, we might just return the area.
            // In a real app we'd use a macro or subquery for the GeoJSON. 
            // For now, we return basic fields.
        }

        return [
            'id' => $this->id,
            'farm_id' => $this->farm_id,
            'name' => $this->name,
            'soil_type' => $this->soil_type?->value,
            'calculated_area' => $this->calculated_area,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
