<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Community\DTOs\CreateReplyData;
use App\Domain\Community\Events\NewReplyPosted;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Policies\ForumContentPolicy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateReplyAction
{
    public function execute(CreateReplyData $data): ForumReply
    {
        return DB::transaction(function () use ($data) {
            $thread = ForumContentPolicy::findVisibleThreadLocked($data->threadId);

            if ($data->parentId !== null) {
                $this->resolveParent($thread, $data->parentId);
            }

            $reply = ForumReply::create([
                'thread_id' => $data->threadId,
                'user_id' => $data->userId,
                'parent_id' => $data->parentId,
                'body' => $data->body,
                'is_anonymous' => $data->isAnonymous,
            ]);

            $thread->increment('reply_count');
            $thread->update(['last_activity_at' => now()]);

            event(new NewReplyPosted($reply->id, $thread->id));

            return $reply;
        });
    }

    private function resolveParent(ForumThread $thread, int $parentId): void
    {
        $parent = ForumReply::whereKey($parentId)->lockForUpdate()->first();

        if (! $parent instanceof ForumReply) {
            throw (new ModelNotFoundException)->setModel(ForumReply::class, $parentId);
        }

        if ($parent->thread_id !== $thread->id) {
            throw new InvalidArgumentException('The parent reply must belong to the same thread.');
        }

        if (! ForumContentPolicy::isReplyVisible($parent, $thread)) {
            throw (new ModelNotFoundException)->setModel(ForumReply::class, $parentId);
        }
    }
}
