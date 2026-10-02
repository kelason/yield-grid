<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Enums;

use App\Constants\CreditScoringConstants;

enum ScoreTier: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case DEVELOPING = 'developing';
    case NEW_FARMER = 'new';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= CreditScoringConstants::TIER_EXCELLENT_MIN => self::EXCELLENT,
            $score >= CreditScoringConstants::TIER_GOOD_MIN => self::GOOD,
            $score >= CreditScoringConstants::TIER_FAIR_MIN => self::FAIR,
            $score >= CreditScoringConstants::TIER_DEVELOPING_MIN => self::DEVELOPING,
            default => self::NEW_FARMER,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Napakahusay',
            self::GOOD => 'Magaling',
            self::FAIR => 'Katamtaman',
            self::DEVELOPING => 'Nagpapaunlad',
            self::NEW_FARMER => 'Baguhan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Outstanding farming track record with consistent marketplace success.',
            self::GOOD => 'Strong farming activity and reliable marketplace participation.',
            self::FAIR => 'Growing presence on the platform with room for improvement.',
            self::DEVELOPING => 'Building farming and marketplace track record.',
            self::NEW_FARMER => 'Your score will become more accurate as you use the platform.',
        };
    }
}
