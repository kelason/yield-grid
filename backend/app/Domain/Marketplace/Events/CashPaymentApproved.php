<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Events;

use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashPaymentApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Purchase $purchase
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->purchase->buyer_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'purchase_id' => $this->purchase->id,
            'message' => 'Cash payment approved by farmer',
            'status' => $this->purchase->cash_payment_status->value,
            'confirmed_amount' => $this->purchase->cash_amount_confirmed,
        ];
    }
}
