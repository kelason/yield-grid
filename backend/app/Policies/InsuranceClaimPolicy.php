<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Insurance\Models\InsuranceClaim;
use Domain\Users\Models\User;

final class InsuranceClaimPolicy
{
    public function view(User $user, InsuranceClaim $claim): bool
    {
        return $user->id === $claim->enrollment->user_id;
    }

    public function manage(User $user, InsuranceClaim $claim): bool
    {
        return $user->id === $claim->enrollment->user_id;
    }
}
