<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Community\DTOs\CreateReplyData;
use App\Domain\Community\Events\NewReplyPosted;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use Illuminate\Support\Facades\DB;

final class CreateReplyAction
{
    public function execute(CreateReplyData $data): ForumReply
    {
        return DB::transaction(function () use ($data) {
            $reply = ForumReply::create([
                'thread_id' => $data->threadId,
                'user_id' => $data->userId,
                'parent_id' => $data->parentId,
                'body' => $data->body,
                'is_anonymous' => $data->isAnonymous,
            ]);

            $thread = ForumThread::lockForUpdate()->findOrFail($data->threadId);
            $thread->increment('reply_count');
            $thread->update(['last_activity_at' => now()]);

            event(new NewReplyPosted($reply));

            return $reply;
        });
    }
}
