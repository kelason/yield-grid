<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;

final class CropDemandPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, CropDemand $demand): bool
    {
        if (! $demand->isHidden()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->id === $demand->buyer_id) {
            return true;
        }

        return CropDemandOffer::where('crop_demand_id', $demand->id)
            ->where('farmer_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::BUYER;
    }

    public function update(User $user, CropDemand $demand): bool
    {
        return $user->id === $demand->buyer_id;
    }

    public function cancel(User $user, CropDemand $demand): bool
    {
        return $user->id === $demand->buyer_id;
    }

    public function decideOffer(User $user, CropDemand $demand): bool
    {
        return $user->id === $demand->buyer_id;
    }

    public function submitOffer(User $user, CropDemand $demand): bool
    {
        return $user->role === UserRole::FARMER && $user->id !== $demand->buyer_id && ! $demand->isHidden();
    }
}
