<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Contact\Models\IssueTicket;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class IssueTicketPolicy
{
    public function create(User $user): bool
    {
        return $this->isVerifiedMember($user);
    }

    public function viewAny(User $user): bool
    {
        return $this->isVerifiedMember($user) || $this->isVerifiedAdmin($user);
    }

    public function view(User $user, IssueTicket $issue): bool
    {
        if ($this->isVerifiedAdmin($user)) {
            return true;
        }

        return $this->isVerifiedMember($user) && $issue->user_id === $user->id;
    }

    public function transition(User $user, IssueTicket $issue): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    private function isVerifiedMember(User $user): bool
    {
        return in_array($user->role, [UserRole::FARMER, UserRole::BUYER], true)
            && $user->hasVerifiedEmail();
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN && $user->hasVerifiedEmail();
    }
}
