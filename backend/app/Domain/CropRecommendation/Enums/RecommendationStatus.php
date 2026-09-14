<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Enums;

enum RecommendationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case FAILED = 'failed';
}
