<?php

declare(strict_types=1);

namespace App\Domain\Farming\DTOs;

use App\Domain\Farming\Enums\FloodRiskLevel;
use Carbon\CarbonImmutable;

readonly class FloodRiskAssessment
{
    /**
     * @param  array<int, string>  $advice
     */
    public function __construct(
        public FloodRiskLevel $level,
        public array $advice,
        public bool $withinCoverage,
        public CarbonImmutable $assessedAt,
    ) {}
}
