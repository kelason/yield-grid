<?php

declare(strict_types=1);

namespace App\Infrastructure\CropRecommendation\Repositories;

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Repositories\CropRecommendationRepositoryInterface;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;

class EloquentCropRecommendationRepository implements CropRecommendationRepositoryInterface
{
    public function findByIdAndFarmer(int $id, int $farmerId): ?CropRecommendation
    {
        $recommendation = CropRecommendation::with('plot.farm')->find($id);

        if (! $recommendation) {
            return null;
        }

        if ($recommendation->plot->farm->user_id !== $farmerId) {
            return null;
        }

        return $recommendation;
    }

    public function markAsPublished(CropRecommendation $recommendation): bool
    {
        return $recommendation->update([
            'status' => RecommendationStatus::ACCEPTED,
            'is_published' => true,
        ]);
    }
}
