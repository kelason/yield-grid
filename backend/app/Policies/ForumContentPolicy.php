<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Central forum visibility rules. Every member read/write path funnels through
 * these helpers so hidden threads, hidden replies, and suppressed descendants
 * behave identically across lists, detail, profiles, mutations, and broadcasts.
 */
final class ForumContentPolicy
{
    public static function isThreadVisible(ForumThread $thread): bool
    {
        return ! $thread->trashed() && ! $thread->isHidden();
    }

    public static function isReplyVisible(ForumReply $reply, ?ForumThread $thread = null): bool
    {
        if ($reply->trashed() || $reply->isHidden()) {
            return false;
        }

        $thread ??= $reply->thread;

        if (! $thread instanceof ForumThread || ! self::isThreadVisible($thread)) {
            return false;
        }

        return self::ancestorsVisible($reply, $thread->id);
    }

    public static function canSubscribeToThread(?User $user, string $threadId): bool
    {
        if ($user === null || $user->isSuspended() || ! ctype_digit($threadId)) {
            return false;
        }

        $thread = ForumThread::whereKey((int) $threadId)->first();

        return $thread instanceof ForumThread && self::isThreadVisible($thread);
    }

    /**
     * Lock a visible thread or fail with 404. Callers that also touch replies
     * must take this lock before any reply lock (thread-before-reply order).
     */
    public static function findVisibleThreadLocked(int $id): ForumThread
    {
        $thread = ForumThread::whereKey($id)->lockForUpdate()->first();

        if (! $thread instanceof ForumThread || ! self::isThreadVisible($thread)) {
            throw (new ModelNotFoundException)->setModel(ForumThread::class, $id);
        }

        return $thread;
    }

    /**
     * Lock a visible reply or fail with 404. The caller must already hold the
     * thread lock to preserve thread-before-reply ordering.
     */
    public static function findVisibleReplyLocked(ForumThread $thread, int $replyId): ForumReply
    {
        if (! self::isThreadVisible($thread)) {
            throw (new ModelNotFoundException)->setModel(ForumThread::class, $thread->id);
        }

        $reply = ForumReply::whereKey($replyId)->lockForUpdate()->first();

        if (! $reply instanceof ForumReply || $reply->thread_id !== $thread->id) {
            throw (new ModelNotFoundException)->setModel(ForumReply::class, $replyId);
        }

        if (! self::isReplyVisible($reply, $thread)) {
            throw (new ModelNotFoundException)->setModel(ForumReply::class, $replyId);
        }

        return $reply;
    }

    /**
     * Drop hidden branches from an already-loaded reply tree without new queries.
     *
     * @param  Collection<int, ForumReply>  $replies
     * @return Collection<int, ForumReply>
     */
    public static function pruneLoadedTree(Collection $replies): Collection
    {
        return $replies
            ->filter(fn (ForumReply $reply): bool => ! $reply->trashed() && ! $reply->isHidden())
            ->each(function (ForumReply $reply): void {
                if ($reply->relationLoaded('children')) {
                    $reply->setRelation('children', self::pruneLoadedTree($reply->children));
                }
            })
            ->values();
    }

    /**
     * Overwrite in-memory reply counts and accepted answers with visible-only
     * values. Stored physical counters are never mutated; never save after this.
     *
     * @param  Collection<int, ForumThread>  $threads
     */
    public static function applyVisibleCounts(Collection $threads): void
    {
        if ($threads->isEmpty()) {
            return;
        }

        $ids = $threads->map(fn (ForumThread $thread): int => $thread->id)->all();

        $grouped = ForumReply::whereIn('thread_id', $ids)->get()->groupBy('thread_id');

        foreach ($threads as $thread) {
            /** @var Collection<int, ForumReply> $candidates */
            $candidates = $grouped->get($thread->id, collect());
            $visibleIds = self::visibleReplyIds($thread, $candidates);

            $thread->setAttribute('reply_count', $visibleIds->count());

            if ($thread->accepted_reply_id !== null && ! $visibleIds->contains($thread->accepted_reply_id)) {
                $thread->setAttribute('accepted_reply_id', null);
            }
        }
    }

    private static function ancestorsVisible(ForumReply $reply, int $threadId): bool
    {
        $seen = [$reply->getKey()];
        $parentId = $reply->parent_id;

        while ($parentId !== null) {
            if (in_array($parentId, $seen, true)) {
                return false;
            }

            $seen[] = $parentId;
            $parent = ForumReply::whereKey($parentId)->first();

            if (! $parent instanceof ForumReply || $parent->thread_id !== $threadId || $parent->isHidden()) {
                return false;
            }

            $parentId = $parent->parent_id;
        }

        return true;
    }

    /**
     * @param  Collection<int, ForumReply>  $candidates
     * @return Collection<int, int>
     */
    private static function visibleReplyIds(ForumThread $thread, Collection $candidates): Collection
    {
        if (! self::isThreadVisible($thread)) {
            return collect();
        }

        $byId = $candidates->keyBy('id');

        return $candidates
            ->filter(fn (ForumReply $reply): bool => self::mappedAncestorsVisible($reply, $byId))
            ->map(fn (ForumReply $reply): int => $reply->id);
    }

    /**
     * @param  Collection<int, ForumReply>  $byId
     */
    private static function mappedAncestorsVisible(ForumReply $reply, Collection $byId): bool
    {
        if ($reply->isHidden()) {
            return false;
        }

        $seen = [$reply->id];
        $parentId = $reply->parent_id;

        while ($parentId !== null) {
            if (in_array($parentId, $seen, true)) {
                return false;
            }

            $seen[] = $parentId;

            /** @var ForumReply|null $parent */
            $parent = $byId->get($parentId);

            if (! $parent instanceof ForumReply || $parent->thread_id !== $reply->thread_id || $parent->isHidden()) {
                return false;
            }

            $parentId = $parent->parent_id;
        }

        return true;
    }
}
