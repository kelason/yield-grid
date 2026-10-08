<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class ContentReportPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::FARMER, UserRole::BUYER], true)
            && $user->hasVerifiedEmail();
    }
}
