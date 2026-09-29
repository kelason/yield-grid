<?php

declare(strict_types=1);

namespace App\Marketplace\Resources;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * @mixin CropDemandOffer
 */
class CropDemandOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $demand = $this->resource->relationLoaded('demand') ? $this->demand : new MissingValue;
        $demandResource = $demand instanceof MissingValue
            ? $demand
            : new CropDemandResource($demand, in_array($this->status->value, DemandOfferStatus::chatEligible(), true));

        return [
            'id' => $this->id,
            'crop_demand_id' => $this->crop_demand_id,
            'quantity_kg' => (float) $this->quantity_kg,
            'price_per_kg' => (float) $this->price_per_kg,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'message' => $this->message,
            'status' => $this->status->value,
            'is_payable' => $this->is_payable,
            'farmer' => [
                'id' => $this->farmer_id,
                'name' => $this->whenLoaded('farmer', fn () => $this->farmer->name),
            ],
            'demand' => $demandResource,
            'purchase' => $this->whenLoaded('purchase', function () {
                return $this->purchase !== null ? [
                    'id' => $this->purchase->id,
                    'payment_status' => $this->purchase->payment_status->value,
                    'amount_paid' => (float) $this->purchase->amount_paid,
                    'is_downpayment' => (bool) $this->purchase->is_downpayment,
                    'total_contract_amount' => $this->purchase->total_contract_amount !== null ? (float) $this->purchase->total_contract_amount : null,
                ] : null;
            }),
            'accepted_at' => $this->accepted_at,
            'paid_at' => $this->paid_at,
            'delivered_at' => $this->delivered_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
