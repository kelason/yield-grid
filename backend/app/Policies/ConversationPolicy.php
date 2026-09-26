<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Chat\Actions\AssertTransactionExistsAction;
use Domain\Users\Models\User;

final class ConversationPolicy
{
    public const CREATE_ABILITY = 'chat.create';

    public function __construct(
        private readonly AssertTransactionExistsAction $assertTransactionExists
    ) {}

    /**
     * A conversation may only be started with a user on the other side of a
     * live transaction (buyer buys from farmer).
     */
    public function create(User $user, int $recipientId): bool
    {
        return $this->assertTransactionExists->execute($user->id, $recipientId);
    }
}
