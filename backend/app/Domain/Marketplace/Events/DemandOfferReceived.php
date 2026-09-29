<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Events;

use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DemandOfferReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CropDemandOffer $offer,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->offer->demand->buyer_id}");
    }

    public function broadcastAs(): string
    {
        return 'demand.offer.received';
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
            'quantity_kg' => (float) $this->offer->quantity_kg,
            'price_per_kg' => (float) $this->offer->price_per_kg,
            'farmer_id' => $this->offer->farmer_id,
        ];
    }
}
