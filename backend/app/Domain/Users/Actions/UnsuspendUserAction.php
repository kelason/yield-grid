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

final class UnsuspendUserAction
{
    private const string SUBJECT_TYPE = 'user';

    public function __construct(
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(User $actor, User $target, string $reason): User
    {
        $reason = trim($reason);

        $this->guardReason($reason);

        return DB::transaction(fn (): User => $this->unsuspendLocked($actor, $target, $reason));
    }

    private function unsuspendLocked(User $actor, User $target, string $reason): User
    {
        $locked = User::where('id', $target->id)->lockForUpdate()->firstOrFail();

        $this->guardUnsuspendable($locked);

        $before = [
            'suspended_at' => $locked->suspended_at?->toISOString(),
            'suspended_reason' => $locked->suspended_reason,
        ];

        $locked->forceFill([
            'suspended_at' => null,
            'suspended_reason' => null,
        ])->save();

        $this->history->append(
            $actor->id,
            AdminAction::USER_UNSUSPENDED,
            self::SUBJECT_TYPE,
            (string) $locked->id,
            $reason,
            $before,
            ['suspended_at' => null],
        );

        return $locked->refresh();
    }

    private function guardReason(string $reason): void
    {
        if ($reason === '' || mb_strlen($reason) > AdminConstants::SUSPENSION_REASON_MAX_LENGTH) {
            throw new InvalidArgumentException('The reason must be between 1 and 500 characters.');
        }
    }

    private function guardUnsuspendable(User $locked): void
    {
        if ($locked->role === UserRole::ADMIN) {
            throw new InvalidArgumentException('Administrators cannot be unsuspended.');
        }

        if (! $locked->isSuspended()) {
            throw new LogicException('User is not suspended.');
        }
    }
}
