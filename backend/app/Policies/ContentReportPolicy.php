<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Shared\Models\ContentReport;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class ContentReportPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::FARMER, UserRole::BUYER], true)
            && $user->hasVerifiedEmail();
    }

    public function viewAny(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function view(User $user, ContentReport $report): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function decide(User $user, ContentReport $report): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN && $user->hasVerifiedEmail();
    }
}
