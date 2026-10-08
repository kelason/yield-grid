<?php

declare(strict_types=1);

namespace App\Domain\Contact\Actions;

use App\Constants\ContactConstants;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class QueueContactReplyAction
{
    private const string SUBJECT_TYPE = 'contact_message_reply';

    public function __construct(
        private readonly ContactMessageReplyRepositoryInterface $replies,
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(User $actor, ContactMessage $message, string $body, string $requestId): ContactMessageReply
    {
        $body = trim($body);

        $this->guardInput($body, $requestId);

        return DB::transaction(fn (): ContactMessageReply => $this->queueLocked($actor, $message, $body, $requestId));
    }

    private function guardInput(string $body, string $requestId): void
    {
        if ($body === '' || mb_strlen($body) > ContactConstants::REPLY_BODY_MAX_LENGTH) {
            throw new InvalidArgumentException('The reply body must be between 1 and 5000 characters.');
        }

        if (! Str::isUuid($requestId)) {
            throw new InvalidArgumentException('The client request id must be a valid UUID.');
        }
    }

    private function queueLocked(User $actor, ContactMessage $message, string $body, string $requestId): ContactMessageReply
    {
        $locked = ContactMessage::where('id', $message->id)->lockForUpdate()->firstOrFail();

        if ($locked->status === ContactStatus::CLOSED) {
            throw new LogicException('Cannot reply to a closed inquiry.');
        }

        $existing = $this->replies->findByRequestId($locked->id, $requestId);

        if ($existing instanceof ContactMessageReply) {
            $this->guardReplay($existing, $body);

            return $existing;
        }

        if ($this->replies->findOutstandingForMessage($locked->id) !== null) {
            throw new LogicException('This inquiry already has an outstanding reply.');
        }

        return $this->storeReply($actor, $locked, $body, $requestId);
    }

    private function storeReply(User $actor, ContactMessage $locked, string $body, string $requestId): ContactMessageReply
    {
        $reply = $this->replies->create([
            'message_id' => $locked->id,
            'admin_id' => $actor->id,
            'recipient' => $locked->email,
            'body' => $body,
            'client_request_id' => $requestId,
            'delivery_status' => ReplyDeliveryStatus::QUEUED,
            'attempts' => 0,
            'delivery_generation' => 0,
        ]);

        $this->history->append(
            $actor->id,
            AdminAction::REPLY_QUEUED,
            self::SUBJECT_TYPE,
            (string) $reply->id,
            'Queued reply for delivery.',
            ['delivery_status' => null],
            ['delivery_status' => ReplyDeliveryStatus::QUEUED->value, 'message_id' => (string) $locked->id],
        );

        return $reply;
    }

    private function guardReplay(ContactMessageReply $existing, string $body): void
    {
        if ($existing->body !== $body) {
            throw new LogicException('This request key was already used with a different reply body.');
        }
    }
}
