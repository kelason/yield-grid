<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Events;

use App\Domain\Marketplace\Models\ForwardContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ContractExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ForwardContract $contract,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->contract->farmer_id}");
    }

    public function broadcastAs(): string
    {
        return 'contract.expired';
    }

    public function broadcastWith(): array
    {
        return [
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'crop_name' => $this->contract->crop_name,
        ];
    }
}
