<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class ForwardContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::FARMER;
    }

    public function view(User $user, ForwardContract $contract): bool
    {
        return $user->id === $contract->farmer_id;
    }

    public function create(User $user): bool
    {
        // @TODO: Re-enable email verification check once the email verification feature is fully implemented
        // return $user->role === UserRole::FARMER && $user->hasVerifiedEmail();
        return $user->role === UserRole::FARMER;
    }

    public function cancel(User $user, ForwardContract $contract): bool
    {
        return $user->id === $contract->farmer_id && $contract->status === ContractStatus::AVAILABLE;
    }
}
