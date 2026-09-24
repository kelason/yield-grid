<?php

declare(strict_types=1);

namespace App\Domain\Community\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThreadVoteUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $threadId,
        public int $voteScore
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('thread.'.$this->threadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ThreadVoteUpdated';
    }
}
