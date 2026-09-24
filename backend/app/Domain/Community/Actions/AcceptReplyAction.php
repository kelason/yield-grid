<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use Illuminate\Support\Facades\DB;

final class AcceptReplyAction
{
    /**
     * Accepts or unaccepts a reply for a thread.
     */
    public function execute(int $threadId, int $replyId): void
    {
        DB::transaction(function () use ($threadId, $replyId) {
            $thread = ForumThread::lockForUpdate()->findOrFail($threadId);
            $reply = ForumReply::findOrFail($replyId);

            // If a reply is already accepted, unaccept it
            if ($thread->accepted_reply_id) {
                ForumReply::where('id', $thread->accepted_reply_id)->update(['is_accepted' => false]);
            }

            // If we are clicking the same reply that's already accepted, we just toggle it off
            if ($thread->accepted_reply_id === $replyId) {
                $thread->update(['accepted_reply_id' => null]);
            } else {
                $reply->update(['is_accepted' => true]);
                $thread->update(['accepted_reply_id' => $replyId]);
            }
        });
    }
}
