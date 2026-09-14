<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalysisCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $plotId;

    public function __construct(int $plotId)
    {
        $this->plotId = $plotId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('plot.' . $this->plotId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AnalysisCompleted';
    }
}
