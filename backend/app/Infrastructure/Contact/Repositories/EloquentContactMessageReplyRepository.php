<?php

declare(strict_types=1);

namespace App\Infrastructure\Contact\Repositories;

use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentContactMessageReplyRepository implements ContactMessageReplyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContactMessageReply
    {
        return ContactMessageReply::create($attributes);
    }

    public function findById(int $id): ContactMessageReply
    {
        return ContactMessageReply::where('id', $id)->firstOrFail();
    }

    public function findLockedById(int $id): ContactMessageReply
    {
        return ContactMessageReply::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function findByRequestId(int $messageId, string $requestId): ?ContactMessageReply
    {
        return ContactMessageReply::where('message_id', $messageId)
            ->where('client_request_id', $requestId)
            ->first();
    }

    public function findOutstandingForMessage(int $messageId): ?ContactMessageReply
    {
        return ContactMessageReply::where('message_id', $messageId)
            ->whereIn('delivery_status', [ReplyDeliveryStatus::QUEUED->value, ReplyDeliveryStatus::SENDING->value])
            ->orderBy('id')
            ->first();
    }

    public function save(ContactMessageReply $reply): void
    {
        $reply->save();
    }

    /**
     * @return Collection<int, ContactMessageReply>
     */
    public function forMessage(int $messageId): Collection
    {
        return ContactMessageReply::where('message_id', $messageId)->orderBy('id')->get();
    }
}
