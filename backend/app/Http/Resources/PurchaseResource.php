<?php

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
            'contract' => new ForwardContractResource($this->whenLoaded('contract')),
            'payment_method' => $this->payment_method?->value,
            'amount_paid' => (float) $this->amount_paid,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status->value,
            'purchased_at' => $this->purchased_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
