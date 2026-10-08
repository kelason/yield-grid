<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Constants\ForumConstants;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ModerateForumContentAction
{
    public function __construct(
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    /**
     * Reversibly hide or restore a forum thread/reply. Records stay in the
     * database; hide only removes in-app visibility, never stored content.
     */
    public function execute(User $actor, ReportTargetType $type, string $id, bool $hide, string $reason): Model
    {
        $reason = trim($reason);

        $this->guardTargetType($type);
        $this->guardReason($reason);

        return DB::transaction(fn (): Model => $type === ReportTargetType::THREAD
            ? $this->moderateThread($actor, (int) $id, $hide, $reason)
            : $this->moderateReply($actor, (int) $id, $hide, $reason));
    }

    private function moderateThread(User $actor, int $id, bool $hide, string $reason): ForumThread
    {
        $thread = ForumThread::whereKey($id)->lockForUpdate()->first();

        if (! $thread instanceof ForumThread) {
            throw (new ModelNotFoundException)->setModel(ForumThread::class, $id);
        }

        $this->transition($actor, ReportTargetType::THREAD, $thread, $hide, $reason);

        return $thread->refresh();
    }

    private function moderateReply(User $actor, int $id, bool $hide, string $reason): ForumReply
    {
        $threadId = ForumReply::whereKey($id)->value('thread_id');

        if ($threadId === null) {
            throw (new ModelNotFoundException)->setModel(ForumReply::class, $id);
        }

        // Thread-before-reply: hold the thread lock before touching the reply.
        ForumThread::whereKey((int) $threadId)->lockForUpdate()->first();

        $reply = ForumReply::whereKey($id)->lockForUpdate()->firstOrFail();

        $this->transition($actor, ReportTargetType::REPLY, $reply, $hide, $reason);

        return $reply->refresh();
    }

    private function transition(User $actor, ReportTargetType $type, Model $model, bool $hide, string $reason): void
    {
        if (($model->getAttribute('hidden_at') !== null) === $hide) {
            throw new LogicException($this->repeatedMessage($type, $hide));
        }

        $before = $this->moderationState($model);

        $model->forceFill($hide
            ? ['hidden_at' => now(), 'hidden_by' => $actor->id, 'hidden_reason' => $reason]
            : ['hidden_at' => null, 'hidden_by' => null, 'hidden_reason' => null]);
        $model->save();

        $this->history->append(
            $actor->id,
            $hide ? AdminAction::CONTENT_HIDDEN : AdminAction::CONTENT_RESTORED,
            $type->value,
            (string) $model->getKey(),
            $reason,
            $before,
            $this->moderationState($model),
        );
    }

    private function guardTargetType(ReportTargetType $type): void
    {
        if ($type !== ReportTargetType::THREAD && $type !== ReportTargetType::REPLY) {
            throw new InvalidArgumentException('Only forum threads and replies can be moderated here.');
        }
    }

    private function guardReason(string $reason): void
    {
        if ($reason === '' || mb_strlen($reason) > ForumConstants::MODERATION_REASON_MAX_LENGTH) {
            throw new InvalidArgumentException('The reason must be between 1 and 500 characters.');
        }
    }

    private function repeatedMessage(ReportTargetType $type, bool $hide): string
    {
        $label = $type === ReportTargetType::THREAD ? 'Thread' : 'Reply';

        return $hide ? "{$label} is already hidden." : "{$label} is not hidden.";
    }

    /**
     * @return array{hidden_at: ?string, hidden_by: ?int, hidden_reason: ?string}
     */
    private function moderationState(Model $model): array
    {
        $hiddenAt = $model->getAttribute('hidden_at');

        return [
            'hidden_at' => $hiddenAt instanceof Carbon ? $hiddenAt->toISOString() : null,
            'hidden_by' => $model->getAttribute('hidden_by'),
            'hidden_reason' => $model->getAttribute('hidden_reason'),
        ];
    }
}
