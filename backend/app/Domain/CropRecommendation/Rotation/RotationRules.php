<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Rotation;

/**
 * Family-level rotation and companion rules. Curated agronomic seed data
 * shared by the AI prompt and the mock scorer.
 */
final class RotationRules
{
    public const SUCCESSION_PENALTY = 25;

    public const SOIL_SEQUENCE_BONUS = 15;

    private const RISKY_SUCCESSION_FAMILIES = [
        'nightshade',
        'cucurbit',
        'brassica',
        'allium',
        'grass',
        'legume',
    ];

    private const AVOID_COMPANIONS = [
        'allium' => ['legume'],
        'legume' => ['allium'],
        'nightshade' => ['grass', 'cucurbit', 'brassica'],
        'grass' => ['nightshade'],
        'cucurbit' => ['nightshade'],
        'brassica' => ['nightshade'],
    ];

    /**
     * Crop-specific clashes that family rules miss. Keys are
     * pipe-joined slugs in alphabetical order.
     *
     * @var array<string, string>
     */
    private const CLASHING_PAIRS = [
        'carrot|dill' => 'Dill stunts nearby carrots — the two cross-pollinate and weaken each other, so keep them apart.',
    ];

    public static function successionPenalty(?string $previousFamily, string $candidateFamily): int
    {
        if ($previousFamily === null || $previousFamily !== $candidateFamily) {
            return 0;
        }

        return in_array($candidateFamily, self::RISKY_SUCCESSION_FAMILIES, true)
            ? self::SUCCESSION_PENALTY
            : 0;
    }

    public static function soilSequenceBonus(?string $previousSoilNeed, string $candidateSoilNeed): int
    {
        $pair = [$previousSoilNeed, $candidateSoilNeed];

        if ($pair === ['builder', 'heavy'] || $pair === ['heavy', 'builder']) {
            return self::SOIL_SEQUENCE_BONUS;
        }

        return 0;
    }

    /**
     * @return list<string> families worth planting after the given one.
     */
    public static function goodFollowups(string $family): array
    {
        return match ($family) {
            'legume' => ['grass', 'nightshade'],
            default => ['legume'],
        };
    }

    /**
     * @return list<string> families to avoid growing alongside the given one.
     */
    public static function avoidCompanions(string $family): array
    {
        return self::AVOID_COMPANIONS[$family] ?? [];
    }

    public static function clashReason(string $slugA, string $slugB): ?string
    {
        $pair = [$slugA, $slugB];
        sort($pair);

        return self::CLASHING_PAIRS[implode('|', $pair)] ?? null;
    }

    public static function promptSection(): string
    {
        return <<<'TEXT'
            Rotation & companion rules: never plant the same family back to back
            (nightshade, cucurbit, brassica, allium, grass, legume all carry
            soil disease pressure); follow heavy feeders with nitrogen-building
            legumes and vice versa. Companion cautions: keep alliums away from
            legumes, nightshades away from corn-family grasses that share pests,
            and nightshades away from cucurbits and brassicas that compete for
            nutrients. Dill stunts nearby carrots — never plant them together.
            TEXT;
    }
}
