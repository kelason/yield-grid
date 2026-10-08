<?php

declare(strict_types=1);

namespace App\Domain\Contact\Actions;

use App\Constants\ContactConstants;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RetryContactReplyAction
{
    private const string SUBJECT_TYPE = 'contact_message_reply';

    private bool $staleSending = false;

    public function __construct(
        private readonly ContactMessageReplyRepositoryInterface $replies,
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(User $actor, ContactMessage $message, ContactMessageReply $reply): ContactMessageReply
    {
        if ($reply->message_id !== $message->id) {
            throw new ModelNotFoundException('Reply does not belong to this inquiry.');
        }

        return DB::transaction(fn (): ContactMessageReply => $this->retryLocked($actor, $message, $reply));
    }

    public function wasStaleSending(): bool
    {
        return $this->staleSending;
    }

    private function retryLocked(User $actor, ContactMessage $message, ContactMessageReply $reply): ContactMessageReply
    {
        ContactMessage::where('id', $message->id)->lockForUpdate()->firstOrFail();
        $locked = $this->replies->findLockedById($reply->id);

        $this->guardRetryable($locked);
        $this->staleSending = $locked->delivery_status === ReplyDeliveryStatus::SENDING;

        $before = ['delivery_status' => $locked->delivery_status->value, 'delivery_generation' => $locked->delivery_generation];

        $locked->forceFill([
            'delivery_status' => ReplyDeliveryStatus::QUEUED,
            'delivery_generation' => $locked->delivery_generation + 1,
            'sending_started_at' => null,
            'error_code' => null,
        ]);
        $this->replies->save($locked);

        $this->history->append(
            $actor->id,
            AdminAction::REPLY_RETRIED,
            self::SUBJECT_TYPE,
            (string) $locked->id,
            'Retried reply delivery.',
            $before,
            ['delivery_status' => ReplyDeliveryStatus::QUEUED->value, 'delivery_generation' => $locked->delivery_generation],
        );

        return $locked->refresh();
    }

    private function guardRetryable(ContactMessageReply $locked): void
    {
        if ($locked->delivery_status === ReplyDeliveryStatus::SENT) {
            throw new LogicException('This reply was already sent.');
        }

        if ($locked->delivery_status === ReplyDeliveryStatus::FAILED) {
            return;
        }

        if (! $this->isStale($locked)) {
            throw new LogicException('This reply is still being delivered.');
        }
    }

    private function isStale(ContactMessageReply $locked): bool
    {
        $reference = $locked->delivery_status === ReplyDeliveryStatus::SENDING
            ? ($locked->sending_started_at ?? $locked->updated_at)
            : $locked->updated_at;

        return $reference !== null && $reference->lt(now()->subSeconds(ContactConstants::REPLY_LEASE_SECONDS));
    }
}
