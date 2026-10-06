<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Taxonomy;

/**
 * Operational profiles for field/staple crops (grains, sugars, oilseeds).
 * See VegetableProfiles for the field vocabulary.
 */
final class FieldCropProfiles
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'lowland-rice' => [
                'name' => 'Lowland Rice (Palay)',
                'subtype' => 'staple', 'family' => 'grass',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field'],
                'light' => ['full_sun'], 'water' => 'high',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['wet'],
            ],
            'corn' => [
                'name' => 'Yellow Corn (Maize)',
                'subtype' => 'staple', 'family' => 'grass',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'moderate',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['wet', 'dry'],
            ],
            'sugarcane' => [
                'name' => 'Sugarcane (Tubo)',
                'subtype' => 'staple', 'family' => 'grass',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'high',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['wet', 'dry'],
            ],
            'sorghum' => [
                'name' => 'Grain Sorghum',
                'subtype' => 'staple', 'family' => 'grass',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'low',
                'soil_need' => 'light', 'harvest' => 'single',
                'seasons' => ['dry'],
            ],
            'sunflower' => [
                'name' => 'Sunflowers (Commercial Sunflower)',
                'subtype' => 'staple', 'family' => 'aster',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'low',
                'soil_need' => 'light', 'harvest' => 'single',
                'seasons' => ['dry'],
            ],
        ];
    }
}
