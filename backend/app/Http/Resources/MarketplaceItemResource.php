<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Marketplace\Models\ForwardContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isContract = $this->resource instanceof ForwardContract;

        return [
            'id' => $this->id,
            'type' => $isContract ? 'contract' : 'listing',
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
            'is_harvest_available' => $isContract ? false : $this->is_harvest_available,
            'farmer' => [
                'id' => $this->farmer->id,
                'name' => $this->farmer->name,
                'farm_name' => $this->farmer->farms->first()?->name ?? 'Farm',
                'location' => $this->farmer->farms->first()?->city ?? 'Unknown',
            ],
            'recommendation' => $isContract ? new CropRecommendationResource($this->whenLoaded('recommendation')) : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
