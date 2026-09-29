<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Events;

use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DemandOfferPaid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Purchase $purchase,
        public readonly CropDemandOffer $offer,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->offer->farmer_id}");
    }

    public function broadcastAs(): string
    {
        return 'demand.offer.paid';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'offer_id' => $this->offer->id,
            'demand_id' => $this->offer->crop_demand_id,
            'crop_name' => $this->offer->demand->crop_name,
            'amount_paid' => (float) $this->purchase->amount_paid,
            'currency' => $this->purchase->currency,
            'purchased_at' => $this->purchase->purchased_at?->toIso8601String(),
        ];
    }
}
