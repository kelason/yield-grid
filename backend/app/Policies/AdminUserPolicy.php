<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class AdminUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function suspend(User $user, User $target): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function unsuspend(User $user, User $target): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN && $user->hasVerifiedEmail();
    }
}
