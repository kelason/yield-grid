<?php

declare(strict_types=1);

namespace App\Marketplace\Resources;

use App\Domain\Marketplace\Models\CropDemand;
use App\Infrastructure\Services\PsgcService;
use App\Users\Resources\UserAddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CropDemand
 */
class CropDemandResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly bool $includeDeliveryAddress = false,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PsgcService $psgc */
        $psgc = app(PsgcService::class);

        $locationSummary = null;
        $deliveryAddress = $this->resource->relationLoaded('deliveryAddress') ? $this->deliveryAddress : null;
        if ($deliveryAddress !== null) {
            $names = $psgc->resolveNames([
                'region_code' => $deliveryAddress->region_code,
                'province_code' => $deliveryAddress->province_code,
                'city_municipality_code' => $deliveryAddress->city_municipality_code,
                'barangay_code' => $deliveryAddress->barangay_code,
            ]);
            $parts = array_filter([$names['city_municipality'], $names['province']]);
            $locationSummary = $parts === [] ? null : implode(', ', $parts);
        }

        $viewer = $request->user();
        $isOwner = $viewer !== null && (int) $viewer->id === (int) $this->buyer_id;
        $showDeliveryAddress = ($this->includeDeliveryAddress || $isOwner) && $deliveryAddress !== null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'crop_name' => $this->crop_name,
            'quantity_kg' => (float) $this->quantity_kg,
            'remaining_quantity_kg' => (float) $this->remaining_quantity_kg,
            'target_price_per_kg' => (float) $this->target_price_per_kg,
            'total_budget' => (float) $this->total_budget,
            'currency' => $this->currency,
            'needed_by_date' => $this->needed_by_date,
            'expiry_date' => $this->expiry_date,
            'status' => $this->status->value,
            'is_open' => $this->is_open,
            'is_hidden' => $this->resource->isHidden(),
            'location_summary' => $locationSummary,
            'distance_m' => $this->resource->distance_m !== null ? (int) round((float) $this->resource->distance_m) : null,
            'offers_count' => $this->whenCounted('offers'),
            'pending_offers_count' => $this->whenCounted('pending_offers_count'),
            'buyer' => [
                'id' => $this->buyer_id,
                'name' => $this->whenLoaded('buyer', fn () => $this->buyer->name),
            ],
            'delivery_address' => $showDeliveryAddress
                ? new UserAddressResource($deliveryAddress)
                : null,
            'offers' => CropDemandOfferResource::collection($this->whenLoaded('offers')),
            'created_at' => $this->created_at,
        ];
    }
}
