<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class CreditScorePolicy
{
    public function viewOwn(User $user): bool
    {
        return $user->role === UserRole::FARMER;
    }

    public function generateReport(User $user): bool
    {
        return $user->role === UserRole::FARMER && $user->hasVerifiedEmail();
    }

    public function download(User $user, CreditScoreSnapshot $snapshot): bool
    {
        return $user->id === $snapshot->user_id;
    }
}
