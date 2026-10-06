<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Enums;

enum IrrigationLevel: string
{
    case NONE = 'none';
    case LIMITED = 'limited';
    case RELIABLE = 'reliable';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No irrigation (rainfed only)',
            self::LIMITED => 'Limited irrigation',
            self::RELIABLE => 'Reliable irrigation',
        };
    }
}
