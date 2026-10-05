<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;

final class ResolveFarmerRegionAction
{
    /**
     * Resolve the farmer's PSGC region code from their addresses.
     * Returns null when the farmer has no address (callers fall back to NATIONAL).
     */
    public function execute(User $user): ?string
    {
        $default = $user->addresses()->where('is_default', true)->first();

        if ($default !== null) {
            return $default->region_code;
        }

        return $user->addresses()->orderBy('id')->first()?->region_code;
    }

    /**
     * Resolve regions for many users in one query. Users without an
     * address are absent from the map (callers fall back to NATIONAL).
     *
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    public function regionsForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $regions = [];
        $addresses = UserAddress::whereIn('user_id', $userIds)->orderBy('id')->get();

        foreach ($addresses->groupBy('user_id') as $userId => $group) {
            $default = $group->firstWhere('is_default', true);

            $regions[(int) $userId] = ($default ?? $group->first())->region_code;
        }

        return $regions;
    }
}
