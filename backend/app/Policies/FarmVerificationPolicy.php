<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class FarmVerificationPolicy
{
    public const string VIEW_ANY_ABILITY = 'farm-verification.view-any';

    public const string VIEW_ABILITY = 'farm-verification.view';

    public const string DECIDE_ABILITY = 'farm-verification.decide';

    public function viewAny(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function view(User $user, Farm|Plot $target): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function decide(User $user, Farm|Plot $target): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN && $user->hasVerifiedEmail();
    }
}
