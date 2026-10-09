<?php

declare(strict_types=1);

namespace App\Farming\Resources;

use Domain\Farming\Enums\VerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'farm_name' => $this->whenLoaded('farm', fn () => $this->farm?->name),
            'farm' => $this->whenLoaded('farm', fn () => [
                'id' => $this->farm?->id,
                'name' => $this->farm?->name,
                'verification_status' => $this->farm?->verification_status?->value,
            ]),
            'name' => $this->name,
            'soil_type' => $this->soil_type?->value,
            'calculated_area' => (float) ($this->calculated_area ?? 0),
            'recommendations_count' => $this->whenCounted('recommendations', $this->recommendations_count),
            'verification_status' => $this->verification_status?->value,
            'verification_note' => $this->when($this->verification_status === VerificationStatus::REJECTED, $this->verification_note),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
