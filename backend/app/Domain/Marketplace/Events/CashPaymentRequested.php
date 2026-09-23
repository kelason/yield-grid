<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Events;

use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashPaymentRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Purchase $purchase
    ) {}

    public function broadcastOn(): array
    {
        $farmerId = $this->purchase->contract
            ? $this->purchase->contract->farmer_id
            : ($this->purchase->harvestListing ? $this->purchase->harvestListing->farmer_id : null);

        if (! $farmerId) {
            return [];
        }

        return [
            new PrivateChannel('user.'.$farmerId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'purchase_id' => $this->purchase->id,
            'message' => 'New cash payment requested',
            'amount' => $this->purchase->total_contract_amount,
        ];
    }
}
