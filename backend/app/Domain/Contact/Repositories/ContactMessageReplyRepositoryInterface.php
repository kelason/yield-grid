<?php

declare(strict_types=1);

namespace App\Domain\Contact\Repositories;

use App\Domain\Contact\Models\ContactMessageReply;
use Illuminate\Database\Eloquent\Collection;

interface ContactMessageReplyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContactMessageReply;

    public function findById(int $id): ContactMessageReply;

    /**
     * Find a reply with a row-level write lock.
     * Must be called inside a transaction.
     */
    public function findLockedById(int $id): ContactMessageReply;

    public function findByRequestId(int $messageId, string $requestId): ?ContactMessageReply;

    public function findOutstandingForMessage(int $messageId): ?ContactMessageReply;

    public function save(ContactMessageReply $reply): void;

    /**
     * @return Collection<int, ContactMessageReply>
     */
    public function forMessage(int $messageId): Collection;

    /**
     * Database-wide reply totals keyed by delivery-status value. Every
     * delivery status is present even when its count is zero.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array;
}
