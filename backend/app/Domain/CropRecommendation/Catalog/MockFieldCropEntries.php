<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Catalog;

/**
 * Mock field/staple entries: agronomic scoring inputs keyed by taxonomy slug.
 */
final class MockFieldCropEntries
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => 'lowland-rice',
                'name' => 'Lowland Rice (Palay)',
                'ideal_soils' => ['clay', 'silt'],
                'unsuitable_soils' => ['sandy'],
                'regions' => ['nueva ecija', 'tarlac', 'central luzon', 'pangasinan', 'isabela'],
                'min_area' => 0.1, 'max_area' => 50.0,
                'yield_ha' => 5.8, 'unit' => 'tons',
                'confidence' => 96,
                'soil_reason' => 'Heavy :soil soil creates an impermeable hardpan that retains standing water efficiently, drastically reducing pumping and irrigation expenses.',
                'loc_reason' => 'Direct access to major rice trading stations and drying mills across :loc.',
            ],
            [
                'slug' => 'sugarcane',
                'name' => 'Sugarcane (Tubo)',
                'ideal_soils' => ['clay', 'loamy'],
                'unsuitable_soils' => ['sandy'],
                'regions' => ['tarlac', 'pampanga', 'negros', 'batangas'],
                'min_area' => 0.5, 'max_area' => 100.0,
                'yield_ha' => 72.0, 'unit' => 'tons',
                'confidence' => 91,
                'soil_reason' => 'Dense :soil soil anchors heavy cane stalks securely against typhoon winds while maintaining continuous moisture for high sugar synthesis.',
                'loc_reason' => 'Strategic proximity to regional sugar centrals and milling facilities in :loc.',
            ],
            [
                'slug' => 'corn',
                'name' => 'Yellow Corn (Maize)',
                'ideal_soils' => ['loamy', 'clay', 'silt'],
                'unsuitable_soils' => [],
                'regions' => ['isabela', 'tarlac', 'nueva ecija', 'pangasinan', 'central luzon'],
                'min_area' => 0.3, 'max_area' => 20.0,
                'yield_ha' => 5.2, 'unit' => 'tons',
                'confidence' => 88,
                'soil_reason' => 'Sturdy root architecture draws deep nutrients from fertile :soil soil.',
                'loc_reason' => 'Surging feed-mill demand from livestock and poultry operators in :loc.',
            ],
            [
                'slug' => 'sorghum',
                'name' => 'Grain Sorghum',
                'ideal_soils' => ['chalky', 'sandy', 'clay'],
                'unsuitable_soils' => [],
                'regions' => ['central luzon', 'mindanao', 'ilocos'],
                'min_area' => 0.5, 'max_area' => 20.0,
                'yield_ha' => 4.6, 'unit' => 'tons',
                'confidence' => 84,
                'soil_reason' => 'Tolerates alkaline pH and mineral-heavy :soil ground where sensitive vegetables struggle.',
                'loc_reason' => 'Reliable climate-resilient feed grain with steady commercial off-takers in :loc.',
            ],
            [
                'slug' => 'sunflower',
                'name' => 'Sunflowers (Commercial Sunflower)',
                'ideal_soils' => ['chalky', 'loamy', 'sandy'],
                'unsuitable_soils' => ['peat'],
                'regions' => ['central luzon', 'nueva ecija', 'tarlac'],
                'min_area' => 0.2, 'max_area' => 10.0,
                'yield_ha' => 3.2, 'unit' => 'tons',
                'confidence' => 86,
                'soil_reason' => 'Deep taproot extracts calcium and micronutrients from alkaline :soil soil with superior drought resilience.',
                'loc_reason' => 'Dual revenue potential from seed harvest and popular local agritourism in :loc.',
            ],
        ];
    }
}
