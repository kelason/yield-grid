<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Catalog;

use App\Domain\CropRecommendation\Rotation\RotationRules;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

/**
 * Contextual score adjustments for the mock catalog: irrigation fit,
 * season fit, and rotation fit against the previous crop.
 */
final class MockContextScoring
{
    public const SCORE_SEASON_MATCH = 15;

    public const PENALTY_NO_IRRIGATION = 50;

    public const PENALTY_LIMITED_IRRIGATION = 25;

    public const SCORE_DEMAND_MATCH = 20;

    /**
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     */
    public static function adjustment(string $slug, array $context): int
    {
        $profile = CropTaxonomy::profile($slug);

        if ($profile === null) {
            return 0;
        }

        return self::irrigationPenalty($profile, $context)
            + self::seasonBonus($profile, $context)
            + self::rotationNet($profile, $context)
            + self::demandBonus($slug, $context);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     */
    private static function irrigationPenalty(array $profile, array $context): int
    {
        if (($profile['water'] ?? null) !== 'high') {
            return 0;
        }

        return match ($context['irrigation'] ?? null) {
            'none' => -self::PENALTY_NO_IRRIGATION,
            'limited' => -self::PENALTY_LIMITED_IRRIGATION,
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     */
    private static function seasonBonus(array $profile, array $context): int
    {
        if (! isset($context['season'])) {
            return 0;
        }

        return in_array($context['season'], $profile['seasons'] ?? [], true)
            ? self::SCORE_SEASON_MATCH
            : 0;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     */
    private static function rotationNet(array $profile, array $context): int
    {
        $worstPenalty = 0;
        $bestBonus = 0;

        foreach ($context['previous_crops'] ?? [] as $previousSlug) {
            $previous = CropTaxonomy::profile($previousSlug);

            if ($previous === null) {
                continue;
            }

            $worstPenalty = min($worstPenalty, -RotationRules::successionPenalty($previous['family'], $profile['family']));
            $bestBonus = max($bestBonus, RotationRules::soilSequenceBonus($previous['soil_need'], $profile['soil_need']));
        }

        return $worstPenalty + $bestBonus;
    }

    /**
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     */
    private static function demandBonus(string $slug, array $context): int
    {
        foreach ($context['demands'] ?? [] as $demand) {
            if (CropTaxonomy::matchSlug((string) ($demand['crop'] ?? '')) === $slug) {
                return self::SCORE_DEMAND_MATCH;
            }
        }

        return 0;
    }
}
