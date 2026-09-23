<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'contract' => $this->contract ? new MarketplaceItemResource($this->contract) : ($this->harvestListing ? new MarketplaceItemResource($this->harvestListing) : null),
            'amount_paid' => (float) $this->amount_paid,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status->value,
            'cash_payment_status' => $this->cash_payment_status?->value,
            'cash_amount_confirmed' => (float) $this->cash_amount_confirmed,
            'is_downpayment' => (bool) $this->is_downpayment,
            'total_contract_amount' => (float) $this->total_contract_amount,
            'purchased_at' => $this->purchased_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
