<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Enums;

enum AnalysisGoal: string
{
    case MAX_PROFIT = 'max_profit';
    case QUICK_CASH = 'quick_cash';
    case FOOD_SECURITY = 'food_security';

    public function label(): string
    {
        return match ($this) {
            self::MAX_PROFIT => 'Maximize profit',
            self::QUICK_CASH => 'Quick cash turnover',
            self::FOOD_SECURITY => 'Household food security',
        };
    }
}
