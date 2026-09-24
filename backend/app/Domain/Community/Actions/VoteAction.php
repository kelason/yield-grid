<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Constants\HttpCode;
use App\Domain\Community\Events\ThreadVoteUpdated;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Community\Models\ReplyVote;
use App\Domain\Community\Models\ThreadVote;
use Illuminate\Support\Facades\DB;

final class VoteAction
{
    /**
     * Toggles a user's vote on a thread.
     */
    public function voteThread(int $userId, int $threadId, int $value): ForumThread
    {
        return DB::transaction(function () use ($userId, $threadId, $value) {
            $thread = ForumThread::lockForUpdate()->findOrFail($threadId);

            if ($thread->user_id === $userId) {
                abort(HttpCode::FORBIDDEN, 'You cannot vote on your own thread.');
            }

            $existingVote = ThreadVote::where('user_id', $userId)
                ->where('thread_id', $threadId)
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
                    'thread_id' => $threadId,
                    'value' => $value,
                ]);
                $thread->increment('vote_score', $value);
            }

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
            $reply = ForumReply::lockForUpdate()->findOrFail($replyId);

            if ($reply->user_id === $userId) {
                abort(HttpCode::FORBIDDEN, 'You cannot vote on your own reply.');
            }

            $existingVote = ReplyVote::where('user_id', $userId)
                ->where('reply_id', $replyId)
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
                    'reply_id' => $replyId,
                    'value' => $value,
                ]);
                $reply->increment('vote_score', $value);
            }

            return $reply->fresh();
        });
    }
}
