<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Repositories;

use App\Infrastructure\CropRecommendation\Models\CropRecommendation;

interface CropRecommendationRepositoryInterface
{
    public function findByIdAndFarmer(int $id, int $farmerId): ?CropRecommendation;

    public function markAsPublished(CropRecommendation $recommendation): bool;
}
