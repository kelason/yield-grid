<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Contact\Models\ContactMessage;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function read(User $user, ContactMessage $message): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function close(User $user, ContactMessage $message): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function reopen(User $user, ContactMessage $message): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function reply(User $user, ContactMessage $message): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function retry(User $user, ContactMessage $message): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN && $user->hasVerifiedEmail();
    }
}
