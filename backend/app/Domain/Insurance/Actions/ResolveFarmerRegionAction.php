<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use Domain\Users\Models\User;

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
}
