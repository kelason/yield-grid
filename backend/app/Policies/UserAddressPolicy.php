<?php

declare(strict_types=1);

namespace App\Policies;

use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;

final class UserAddressPolicy
{
    public function view(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function update(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function delete(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }
}
