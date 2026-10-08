<?php

declare(strict_types=1);

namespace App\Domain\Community\Events;

use App\Domain\Community\Models\ForumReply;
use App\Policies\ForumContentPolicy;
use Domain\Users\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReplyPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $replyId,
        public int $threadId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        if (! $this->evaluateVisibleReply() instanceof ForumReply) {
            return [];
        }

        return [
            new PrivateChannel('thread.'.$this->threadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewReplyPosted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $reply = $this->evaluateVisibleReply();

        if (! $reply instanceof ForumReply) {
            return [];
        }

        return ['reply' => $this->sanitizedReply($reply)];
    }

    private function evaluateVisibleReply(): ?ForumReply
    {
        $reply = ForumReply::whereKey($this->replyId)->first();

        if (! $reply instanceof ForumReply || $reply->thread_id !== $this->threadId) {
            return null;
        }

        return ForumContentPolicy::isReplyVisible($reply) ? $reply : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizedReply(ForumReply $reply): array
    {
        return [
            'id' => $reply->id,
            'thread_id' => $reply->thread_id,
            'parent_id' => $reply->parent_id,
            'body' => $reply->body,
            'vote_score' => $reply->vote_score,
            'is_accepted' => $reply->is_accepted,
            'author' => $this->sanitizedAuthor($reply),
            'created_at' => $reply->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizedAuthor(ForumReply $reply): array
    {
        $author = $reply->author;

        if ($reply->is_anonymous || ! $author instanceof User) {
            return [
                'name' => 'Anonymous Farmer',
                'avatar_url' => null,
                'role' => 'farmer',
            ];
        }

        return [
            'id' => $author->id,
            'name' => $author->name,
            'avatar_url' => $author->avatar_url,
            'role' => $author->role->value,
        ];
    }
}
