<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Enums;

enum CompatibilityVerdict: string
{
    case COMPATIBLE = 'compatible';
    case CAUTION = 'caution';
    case AVOID = 'avoid';

    public function label(): string
    {
        return match ($this) {
            self::COMPATIBLE => 'Compatible',
            self::CAUTION => 'Caution',
            self::AVOID => 'Avoid',
        };
    }
}
