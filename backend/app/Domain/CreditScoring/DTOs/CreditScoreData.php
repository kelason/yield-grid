<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\DTOs;

use App\Domain\CreditScoring\Enums\ScoreTier;

final readonly class CreditScoreData
{
    /**
     * @param  list<array{dimension: string, message: string}>  $improvementTips
     * @param  array<string, mixed>  $rawMetrics
     */
    public function __construct(
        public int $overallScore,
        public ScoreTier $tier,
        public ScoreBreakdownData $breakdown,
        public array $rawMetrics,
        public array $improvementTips,
    ) {}
}
