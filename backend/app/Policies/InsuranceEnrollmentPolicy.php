<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Insurance\Models\InsuranceEnrollment;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class InsuranceEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::FARMER;
    }

    public function view(User $user, InsuranceEnrollment $enrollment): bool
    {
        return $user->id === $enrollment->user_id;
    }

    public function manage(User $user, InsuranceEnrollment $enrollment): bool
    {
        return $user->id === $enrollment->user_id;
    }
}
