<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class InsuranceProfilePolicy
{
    public function viewOwn(User $user): bool
    {
        return $user->role === UserRole::FARMER;
    }
}
