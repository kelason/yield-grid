<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Taxonomy;

/**
 * Operational profiles for fruit crops. See VegetableProfiles for the
 * field vocabulary.
 */
final class FruitProfiles
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'pineapple' => [
                'name' => 'Pineapple (Pinya)',
                'subtype' => 'tropical_tree', 'family' => 'bromeliad',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'low',
                'soil_need' => 'light', 'harvest' => 'single',
                'seasons' => ['wet', 'dry'],
            ],
            'coconut' => [
                'name' => 'Commercial Coconut Grove',
                'subtype' => 'tropical_tree', 'family' => 'palm',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'moderate',
                'soil_need' => 'light', 'harvest' => 'continuous',
                'seasons' => ['wet', 'dry'],
            ],
            'banana' => [
                'name' => 'Banana (Lakatan)',
                'subtype' => 'tropical_tree', 'family' => 'banana',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun'], 'water' => 'high',
                'soil_need' => 'heavy', 'harvest' => 'continuous',
                'seasons' => ['wet', 'dry'],
            ],
            'mango' => [
                'name' => 'Mango (Carabao)',
                'subtype' => 'tropical_tree', 'family' => 'cashew',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'low',
                'soil_need' => 'light', 'harvest' => 'single',
                'seasons' => ['dry'],
            ],
            'calamansi' => [
                'name' => 'Calamansi',
                'subtype' => 'citrus', 'family' => 'citrus',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun'], 'water' => 'moderate',
                'soil_need' => 'heavy', 'harvest' => 'continuous',
                'seasons' => ['wet', 'dry'],
            ],
            'pomelo' => [
                'name' => 'Pomelo (Suha)',
                'subtype' => 'citrus', 'family' => 'citrus',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun'], 'water' => 'moderate',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['wet', 'dry'],
            ],
            'dalandan' => [
                'name' => 'Orange (Dalandan)',
                'subtype' => 'citrus', 'family' => 'citrus',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun'], 'water' => 'moderate',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['wet', 'dry'],
            ],
            'watermelon' => [
                'name' => 'Watermelon (Pakwan)',
                'subtype' => 'vine_ground', 'family' => 'cucurbit',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'high',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['dry'],
            ],
            'cantaloupe' => [
                'name' => 'Cantaloupe (Melon)',
                'subtype' => 'vine_ground', 'family' => 'cucurbit',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'high',
                'soil_need' => 'heavy', 'harvest' => 'single',
                'seasons' => ['dry'],
            ],
            'dragon-fruit' => [
                'name' => 'Dragon Fruit',
                'subtype' => 'vine_ground', 'family' => 'cactus',
                'growth_cycle' => 'long', 'cultivation' => ['open_field', 'trellised'],
                'light' => ['full_sun', 'heat_tolerant'], 'water' => 'low',
                'soil_need' => 'light', 'harvest' => 'continuous',
                'seasons' => ['wet', 'dry'],
            ],
            'strawberry' => [
                'name' => 'Highland Strawberries',
                'subtype' => 'berry', 'family' => 'rose',
                'growth_cycle' => 'mid', 'cultivation' => ['open_field', 'greenhouse'],
                'light' => ['full_sun', 'cool_season'], 'water' => 'moderate',
                'soil_need' => 'heavy', 'harvest' => 'continuous',
                'seasons' => ['dry'],
            ],
            'mulberry' => [
                'name' => 'Mulberry',
                'subtype' => 'berry', 'family' => 'mulberry',
                'growth_cycle' => 'long', 'cultivation' => ['open_field'],
                'light' => ['full_sun'], 'water' => 'low',
                'soil_need' => 'light', 'harvest' => 'continuous',
                'seasons' => ['wet', 'dry'],
            ],
            'blueberry' => [
                'name' => 'Blueberry (Highland)',
                'subtype' => 'berry', 'family' => 'heath',
                'growth_cycle' => 'long', 'cultivation' => ['open_field', 'greenhouse'],
                'light' => ['full_sun', 'cool_season'], 'water' => 'high',
                'soil_need' => 'light', 'harvest' => 'continuous',
                'seasons' => ['dry'],
            ],
        ];
    }
}
