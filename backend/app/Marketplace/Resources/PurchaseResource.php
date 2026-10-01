<?php

declare(strict_types=1);

namespace App\Marketplace\Resources;

use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
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
            'buyer' => $this->buyer ? [
                'id' => $this->buyer->id,
                'name' => $this->buyer->name,
            ] : null,
            'contract' => $this->contract ? new MarketplaceItemResource($this->contract) : ($this->harvestListing ? new MarketplaceItemResource($this->harvestListing) : null),
            'demand_offer' => $this->demandOffer ? [
                'id' => $this->demandOffer->id,
                'crop_demand_id' => $this->demandOffer->crop_demand_id,
                'demand_title' => $this->demandOffer->demand?->title,
                'crop_name' => $this->demandOffer->demand?->crop_name,
                'quantity_kg' => (float) $this->demandOffer->quantity_kg,
                'price_per_kg' => (float) $this->demandOffer->price_per_kg,
                'status' => $this->demandOffer->status->value,
                'farmer' => $this->demandOffer->farmer ? [
                    'id' => $this->demandOffer->farmer->id,
                    'name' => $this->demandOffer->farmer->name,
                ] : null,
            ] : null,
            'amount_paid' => (float) $this->amount_paid,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method?->value,
            'cash_payment_status' => $this->cash_payment_status?->value,
            'cash_amount_confirmed' => (float) $this->cash_amount_confirmed,
            'is_downpayment' => (bool) $this->is_downpayment,
            'total_contract_amount' => (float) $this->total_contract_amount,
            'purchased_at' => $this->purchased_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
