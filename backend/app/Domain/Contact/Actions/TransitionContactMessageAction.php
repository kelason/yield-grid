<?php

declare(strict_types=1);

namespace App\Domain\Contact\Actions;

use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class TransitionContactMessageAction
{
    private const string SUBJECT_TYPE = 'contact_message';

    public function __construct(
        private readonly ContactMessageReplyRepositoryInterface $replies,
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(
        User $actor,
        ContactMessage $message,
        ContactStatus $next,
        ?ContactStatus $from = null,
    ): ContactMessage {
        return DB::transaction(fn (): ContactMessage => $this->transitionLocked($actor, $message, $next, $from));
    }

    private function transitionLocked(
        User $actor,
        ContactMessage $message,
        ContactStatus $next,
        ?ContactStatus $from,
    ): ContactMessage {
        $locked = ContactMessage::where('id', $message->id)->lockForUpdate()->firstOrFail();

        if ($from !== null && $locked->status !== $from) {
            throw new LogicException("This transition requires the inquiry to be {$from->value}.");
        }

        $this->guardTransition($locked->status, $next);

        if ($next === ContactStatus::CLOSED) {
            $this->guardNoOutstanding($locked->id);
        }

        $before = $locked->status;
        $locked->forceFill(['status' => $next])->save();

        $this->history->append(
            $actor->id,
            $this->historyAction($before, $next),
            self::SUBJECT_TYPE,
            (string) $locked->id,
            $this->historyReason($next),
            ['status' => $before->value],
            ['status' => $next->value],
        );

        return $locked->refresh();
    }

    private function guardTransition(ContactStatus $current, ContactStatus $next): void
    {
        $allowed = [
            ContactStatus::UNREAD->value => [ContactStatus::READ, ContactStatus::CLOSED],
            ContactStatus::READ->value => [ContactStatus::CLOSED],
            ContactStatus::REPLIED->value => [ContactStatus::CLOSED],
            ContactStatus::CLOSED->value => [ContactStatus::READ],
        ];

        if (! in_array($next, $allowed[$current->value] ?? [], true)) {
            throw new LogicException("Cannot transition inquiry from {$current->value} to {$next->value}.");
        }
    }

    private function guardNoOutstanding(int $messageId): void
    {
        if ($this->replies->findOutstandingForMessage($messageId) !== null) {
            throw new LogicException('Cannot close an inquiry with an outstanding reply delivery.');
        }
    }

    private function historyAction(ContactStatus $before, ContactStatus $next): AdminAction
    {
        if ($next === ContactStatus::CLOSED) {
            return AdminAction::CONTACT_CLOSED;
        }

        return $before === ContactStatus::CLOSED ? AdminAction::CONTACT_REOPENED : AdminAction::CONTACT_READ;
    }

    private function historyReason(ContactStatus $next): string
    {
        return match ($next) {
            ContactStatus::READ => 'Marked inquiry as read.',
            ContactStatus::CLOSED => 'Closed inquiry.',
            default => 'Updated inquiry status.',
        };
    }
}
