<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForwardContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'crop_name' => $this->crop_name,
            'quantity_kg' => (float) $this->quantity_kg,
            'price_per_kg' => (float) $this->price_per_kg,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'estimated_harvest_date' => $this->estimated_harvest_date->format('Y-m-d'),
            'expiry_date' => $this->expiry_date->format('Y-m-d'),
            'status' => $this->status->value,
            'is_purchasable' => $this->is_purchasable,
            'farmer' => [
                'id' => $this->farmer->id,
                'name' => $this->farmer->name,
                'farm_name' => $this->farmer->farms->first()?->name ?? 'Farm',
                'location' => $this->farmer->farms->first()?->city ?? 'Unknown',
            ],
            'recommendation' => new CropRecommendationResource($this->whenLoaded('recommendation')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
