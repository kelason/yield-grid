<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Catalog;

use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

/**
 * Deterministic mock catalog: scores entries by soil, region, and area fit
 * and returns the top 10 shaped recommendations. Used when Gemini is
 * unavailable; identical inputs always yield the same crop order.
 */
final class MockCropCatalog
{
    private const SCORE_BASE = 50;

    private const SCORE_SOIL_MATCH = 40;

    private const SCORE_SOIL_TOLERATED = 15;

    private const PENALTY_SOIL_MISMATCH = 60;

    private const SCORE_REGION_MATCH = 35;

    private const SCORE_REGION_PARTIAL_MATCH = 25;

    private const SCORE_AREA_MATCH = 15;

    private const PENALTY_AREA_MISMATCH = 20;

    private const TOP_COUNT = 10;

    private const MIN_CONFIDENCE = 75;

    private const MAX_CONFIDENCE = 98;

    private const CONFIDENCE_JITTER = 2;

    private const MIN_AREA_HA = 0.01;

    /**
     * Legacy entry order. New crops append after it so long-standing score
     * ties keep resolving exactly as before.
     *
     * @var list<string>
     */
    private const LEGACY_ORDER = [
        'sweet-potato', 'peanut', 'watermelon', 'cassava', 'lowland-rice',
        'sugarcane', 'taro', 'red-onion', 'tomato', 'eggplant',
        'ampalaya', 'corn', 'chili', 'strawberry', 'cabbage',
        'pineapple', 'sunflower', 'sorghum', 'coconut', 'mungbean',
        'sitaw', 'okra', 'squash', 'ginger', 'garlic',
        'cucumber', 'malunggay',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function entries(): array
    {
        $bySlug = [];

        foreach (array_merge(
            MockVegetableEntries::all(),
            MockFruitEntries::all(),
            MockFieldCropEntries::all(),
        ) as $entry) {
            $bySlug[$entry['slug']] = $entry;
        }

        $ordered = [];

        foreach (self::LEGACY_ORDER as $slug) {
            $ordered[] = $bySlug[$slug] ?? throw new \LogicException("Legacy mock crop '{$slug}' is missing from the catalog entries.");
            unset($bySlug[$slug]);
        }

        return array_merge($ordered, array_values($bySlug));
    }

    /**
     * @param  array{city?: string, state?: string, country?: string}  $location
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     * @return list<array{crop_name: string, confidence_score: int, reasoning: string, projected_yield: string, produce_type: string|null, subtype: string|null}>
     */
    public static function recommend(float $area, string $soil, array $location, array $context = []): array
    {
        $area = max(self::MIN_AREA_HA, $area);
        $soil = strtolower($soil);
        [$locDisplay, $locLower] = self::normalizeLocation($location);
        [$pool, $preferred] = MockCropPool::resolve($context['subtypes'] ?? []);

        $scored = [];

        foreach ($pool as $crop) {
            $scored[] = [
                'crop' => $crop,
                'score' => self::score($crop, $soil, $locLower, $area, $context),
                'preferred' => in_array($crop['slug'], $preferred, true),
            ];
        }

        usort($scored, fn (array $a, array $b): int => [$b['preferred'], $b['score']] <=> [$a['preferred'], $a['score']]);

        return self::format(
            array_slice($scored, 0, self::TOP_COUNT),
            $area,
            number_format($area, 2),
            $soil,
            $locDisplay
        );
    }

    /**
     * @param  array{city?: string, state?: string, country?: string}  $location
     * @return array{0: string, 1: string}
     */
    private static function normalizeLocation(array $location): array
    {
        $city = $location['city'] ?? 'Local Region';
        $state = $location['state'] ?? '';
        $display = trim($city.($state && stripos($city, $state) === false ? ", $state" : ''));

        return [$display, strtolower($display.' '.($location['country'] ?? 'philippines'))];
    }

    /**
     * @param  array<string, mixed>  $crop
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     */
    private static function score(array $crop, string $soil, string $locLower, float $area, array $context): int
    {
        return self::SCORE_BASE
            + self::scoreSoil($crop, $soil)
            + self::scoreRegion($crop, $locLower)
            + self::scoreArea($crop, $area)
            + MockContextScoring::adjustment($crop['slug'], $context);
    }

    /**
     * @param  array<string, mixed>  $crop
     */
    private static function scoreSoil(array $crop, string $soil): int
    {
        if (in_array($soil, $crop['ideal_soils'], true)) {
            return self::SCORE_SOIL_MATCH;
        }

        if (in_array($soil, $crop['unsuitable_soils'], true)) {
            return -self::PENALTY_SOIL_MISMATCH;
        }

        return self::SCORE_SOIL_TOLERATED;
    }

    /**
     * @param  array<string, mixed>  $crop
     */
    private static function scoreRegion(array $crop, string $locLower): int
    {
        foreach ($crop['regions'] as $region) {
            if (str_contains($locLower, $region)) {
                return self::SCORE_REGION_MATCH;
            }
        }

        $nearCentralLuzon = str_contains($locLower, 'tarlac')
            || str_contains($locLower, 'nueva ecija')
            || str_contains($locLower, 'pampanga')
            || str_contains($locLower, 'bulacan');

        if (in_array('central luzon', $crop['regions'], true) && $nearCentralLuzon) {
            return self::SCORE_REGION_PARTIAL_MATCH;
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $crop
     */
    private static function scoreArea(array $crop, float $area): int
    {
        if ($area >= $crop['min_area'] && $area <= $crop['max_area']) {
            return self::SCORE_AREA_MATCH;
        }

        return $area < $crop['min_area'] ? -self::PENALTY_AREA_MISMATCH : 0;
    }

    /**
     * @param  list<array{crop: array<string, mixed>, score: int}>  $top
     * @return list<array{crop_name: string, confidence_score: int, reasoning: string, projected_yield: string, produce_type: string|null, subtype: string|null}>
     */
    private static function format(array $top, float $area, string $areaText, string $soil, string $locDisplay): array
    {
        $recommendations = [];

        foreach ($top as $item) {
            $crop = $item['crop'];
            $profile = CropTaxonomy::profile($crop['slug']) ?? ['type' => null, 'subtype' => null];
            $soilReason = str_replace(':soil', $soil, $crop['soil_reason']);
            $locReason = str_replace(':loc', $locDisplay ?: 'your area', $crop['loc_reason']);

            $recommendations[] = [
                'crop_name' => $crop['name'],
                'confidence_score' => min(self::MAX_CONFIDENCE, max(self::MIN_CONFIDENCE, $crop['confidence'] + rand(-self::CONFIDENCE_JITTER, self::CONFIDENCE_JITTER))),
                'reasoning' => "{$soilReason} {$locReason} Perfectly scaled for {$areaText} hectares.",
                'projected_yield' => self::formatYield($crop, $area, $areaText),
                'produce_type' => $profile['type'],
                'subtype' => $profile['subtype'],
            ];
        }

        return $recommendations;
    }

    /**
     * @param  array<string, mixed>  $crop
     */
    private static function formatYield(array $crop, float $area, string $areaText): string
    {
        if ($crop['unit'] === 'nuts') {
            return number_format(round($area * $crop['yield_ha'], 2)).' nuts/yr ('.$areaText.' ha grove)';
        }

        $total = number_format(round($area * $crop['yield_ha'], 2), 2);
        $perHa = number_format($crop['yield_ha'], 1);

        return "{$total} {$crop['unit']} total ({$perHa} {$crop['unit']}/ha on {$areaText} ha)";
    }
}
