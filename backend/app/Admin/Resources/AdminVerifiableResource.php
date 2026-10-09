<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use Domain\Farming\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminVerifiableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isFarm = $this->resource instanceof Farm;

        return array_merge(
            $this->baseFields($isFarm),
            $isFarm ? $this->farmFields() : $this->plotFields()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function baseFields(bool $isFarm): array
    {
        $farmer = $isFarm ? $this->user : $this->farm?->user;

        return [
            'type' => $isFarm ? 'farm' : 'plot',
            'id' => $this->id,
            'name' => $this->name,
            'verification_status' => $this->verification_status?->value,
            'verification_method' => $this->verification_method?->value,
            'verification_note' => $this->verification_note,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'farmer' => [
                'id' => $farmer?->id,
                'name' => $farmer?->name,
                'email' => $farmer?->email,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function farmFields(): array
    {
        return [
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip' => $this->zip,
            'total_area' => $this->total_area,
            'plots_count' => $this->whenCounted('plots'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function plotFields(): array
    {
        return [
            'farm' => [
                'id' => $this->farm?->id,
                'name' => $this->farm?->name,
            ],
            'soil_type' => $this->soil_type?->value,
            'calculated_area' => $this->calculated_area !== null ? (float) $this->calculated_area : null,
            'geojson' => $this->geojson !== null && $this->geojson !== ''
                ? ['geometry' => json_decode((string) $this->geojson, true)]
                : null,
        ];
    }
}
