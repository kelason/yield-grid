<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Constants\AdminConstants;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class SuspendUserAction
{
    private const string SUBJECT_TYPE = 'user';

    public function __construct(
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(User $actor, User $target, string $reason): User
    {
        $reason = trim($reason);

        $this->guardReason($reason);

        return DB::transaction(fn (): User => $this->suspendLocked($actor, $target, $reason));
    }

    private function suspendLocked(User $actor, User $target, string $reason): User
    {
        $locked = User::where('id', $target->id)->lockForUpdate()->firstOrFail();

        $this->guardSuspendable($locked);

        $suspendedAt = now();

        $locked->tokens()->delete();
        $locked->forceFill([
            'suspended_at' => $suspendedAt,
            'suspended_reason' => $reason,
        ])->save();

        $this->history->append(
            $actor->id,
            AdminAction::USER_SUSPENDED,
            self::SUBJECT_TYPE,
            (string) $locked->id,
            $reason,
            ['suspended_at' => null],
            [
                'suspended_at' => $suspendedAt->toISOString(),
                'suspended_reason' => $reason,
            ],
        );

        return $locked->refresh();
    }

    private function guardReason(string $reason): void
    {
        if ($reason === '' || mb_strlen($reason) > AdminConstants::SUSPENSION_REASON_MAX_LENGTH) {
            throw new InvalidArgumentException('The reason must be between 1 and 500 characters.');
        }
    }

    private function guardSuspendable(User $locked): void
    {
        if ($locked->role === UserRole::ADMIN) {
            throw new InvalidArgumentException('Administrators cannot be suspended.');
        }

        if ($locked->isSuspended()) {
            throw new LogicException('User is already suspended.');
        }
    }
}
