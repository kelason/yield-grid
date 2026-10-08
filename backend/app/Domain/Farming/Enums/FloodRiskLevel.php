<?php

declare(strict_types=1);

namespace App\Domain\Farming\Enums;

enum FloodRiskLevel: string
{
    case SAFE = 'safe';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case UNKNOWN = 'unknown';
}
