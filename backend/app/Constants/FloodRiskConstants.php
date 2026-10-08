<?php

declare(strict_types=1);

namespace App\Constants;

use App\Domain\Farming\Enums\FloodRiskLevel;

final class FloodRiskConstants
{
    public const string OUTSIDE_COVERAGE_NOTE = 'Note: this area is outside NOAH mapped coverage.';

    public const float BBOX_MAX_SPAN_DEGREES = 5.0;

    public const float ZONE_SIMPLIFY_TOLERANCE = 0.0005;

    public const int MAX_ZONES_PER_RESPONSE = 500;

    public const int DEFAULT_RETURN_PERIOD = 5;

    public static function labelFor(FloodRiskLevel $level): string
    {
        return match ($level) {
            FloodRiskLevel::SAFE => 'Safe',
            FloodRiskLevel::LOW => 'Low',
            FloodRiskLevel::MEDIUM => 'Medium',
            FloodRiskLevel::HIGH => 'High',
            FloodRiskLevel::UNKNOWN => 'Unknown',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function adviceFor(FloodRiskLevel $level, bool $withinCoverage): array
    {
        $advice = match ($level) {
            FloodRiskLevel::HIGH => [
                'This plot sits in a high flood-risk zone.',
                'Build drainage canals before planting season.',
                'Use elevated beds or raised planting rows.',
                'Consider flood-tolerant rice varieties (e.g. NSIC Rc 222).',
            ],
            FloodRiskLevel::MEDIUM => [
                'This plot sits in a medium flood-risk zone.',
                'Clear field drains and waterways before the wet season.',
                'Consider raised beds for vegetables.',
            ],
            FloodRiskLevel::LOW => [
                'This plot sits in a low flood-risk zone.',
                'Keep drains clear during heavy rains.',
            ],
            FloodRiskLevel::SAFE => [
                'This plot is outside mapped flood hazard zones.',
            ],
            FloodRiskLevel::UNKNOWN => [
                'Flood risk is unavailable right now. Your plot is unaffected — try Re-check later.',
            ],
        };

        if ($level === FloodRiskLevel::SAFE && ! $withinCoverage) {
            $advice[] = self::OUTSIDE_COVERAGE_NOTE;
        }

        return $advice;
    }
}
