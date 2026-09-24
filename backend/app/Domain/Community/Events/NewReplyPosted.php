<?php

declare(strict_types=1);

namespace App\Domain\Community\Events;

use App\Domain\Community\Models\ForumReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReplyPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ForumReply $reply) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('thread.'.$this->reply->thread_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewReplyPosted';
    }
}
