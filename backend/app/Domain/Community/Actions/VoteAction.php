<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Constants\HttpCode;
use App\Domain\Community\Events\ThreadVoteUpdated;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Community\Models\ReplyVote;
use App\Domain\Community\Models\ThreadVote;
use App\Policies\ForumContentPolicy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class VoteAction
{
    /**
     * Toggles a user's vote on a thread.
     */
    public function voteThread(int $userId, int $threadId, int $value): ForumThread
    {
        return DB::transaction(function () use ($userId, $threadId, $value) {
            $thread = ForumContentPolicy::findVisibleThreadLocked($threadId);

            if ($thread->user_id === $userId) {
                abort(HttpCode::FORBIDDEN, 'You cannot vote on your own thread.');
            }

            $this->toggleThreadVote($thread, $userId, $value);

            event(new ThreadVoteUpdated($thread->id, $thread->vote_score));

            return $thread->fresh();
        });
    }

    /**
     * Toggles a user's vote on a reply.
     */
    public function voteReply(int $userId, int $replyId, int $value): ForumReply
    {
        return DB::transaction(function () use ($userId, $replyId, $value) {
            $threadId = ForumReply::whereKey($replyId)->value('thread_id');

            if ($threadId === null) {
                throw (new ModelNotFoundException)->setModel(ForumReply::class, $replyId);
            }

            // Thread-before-reply: hold the thread lock before the reply lock.
            $thread = ForumContentPolicy::findVisibleThreadLocked((int) $threadId);
            $reply = ForumContentPolicy::findVisibleReplyLocked($thread, $replyId);

            if ($reply->user_id === $userId) {
                abort(HttpCode::FORBIDDEN, 'You cannot vote on your own reply.');
            }

            $this->toggleReplyVote($reply, $userId, $value);

            return $reply->fresh();
        });
    }

    private function toggleThreadVote(ForumThread $thread, int $userId, int $value): void
    {
        $existingVote = ThreadVote::where('user_id', $userId)
            ->where('thread_id', $thread->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->value === $value) {
                // Clicking the same vote button removes the vote
                $existingVote->delete();
                $thread->decrement('vote_score', $value);
            } else {
                // Changing vote (e.g., from -1 to 1 means a +2 change)
                $diff = $value - $existingVote->value;
                $existingVote->update(['value' => $value]);
                $thread->increment('vote_score', $diff);
            }
        } else {
            // New vote
            ThreadVote::create([
                'user_id' => $userId,
                'thread_id' => $thread->id,
                'value' => $value,
            ]);
            $thread->increment('vote_score', $value);
        }
    }

    private function toggleReplyVote(ForumReply $reply, int $userId, int $value): void
    {
        $existingVote = ReplyVote::where('user_id', $userId)
            ->where('reply_id', $reply->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->value === $value) {
                $existingVote->delete();
                $reply->decrement('vote_score', $value);
            } else {
                $diff = $value - $existingVote->value;
                $existingVote->update(['value' => $value]);
                $reply->increment('vote_score', $diff);
            }
        } else {
            ReplyVote::create([
                'user_id' => $userId,
                'reply_id' => $reply->id,
                'value' => $value,
            ]);
            $reply->increment('vote_score', $value);
        }
    }
}
