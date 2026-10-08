<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

/**
 * Guards the allowlisted admin content endpoints. Targets span five models
 * with their own member policies, so these abilities are registered as named
 * gates instead of a per-model policy binding.
 */
final class AdminContentPolicy
{
    public const string VIEW_ABILITY = 'admin-content.view';

    public const string MODERATE_ABILITY = 'admin-content.moderate';

    public function viewAny(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function moderate(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN && $user->hasVerifiedEmail();
    }
}
