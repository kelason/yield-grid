<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Users\Models\User;

class CropRecommendationPolicy
{
    public function view(User $user, CropRecommendation $recommendation): bool
    {
        return $recommendation->plot?->farm && (int) $recommendation->plot->farm->user_id === (int) $user->id;
    }

    public function update(User $user, CropRecommendation $recommendation): bool
    {
        return $recommendation->plot?->farm && (int) $recommendation->plot->farm->user_id === (int) $user->id;
    }
}
