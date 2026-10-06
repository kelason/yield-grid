<?php

use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

it('exposes the vegetable, fruit, and field-crop produce types', function () {
    expect(CropTaxonomy::typeSlugs())->toBe(['vegetable', 'fruit', 'field_crop']);
});

it('exposes every taxonomy subtype under a valid type', function () {
    $expected = [
        'leafy_greens' => 'vegetable',
        'cruciferous' => 'vegetable',
        'fruiting' => 'vegetable',
        'root_tuber' => 'vegetable',
        'bulb_stem' => 'vegetable',
        'legume_pod' => 'vegetable',
        'herbs' => 'vegetable',
        'tropical_tree' => 'fruit',
        'citrus' => 'fruit',
        'vine_ground' => 'fruit',
        'berry' => 'fruit',
        'staple' => 'field_crop',
    ];

    foreach ($expected as $slug => $type) {
        expect(CropTaxonomy::subtypes()[$slug]['type'] ?? null)->toBe($type, "subtype {$slug}");
    }

    expect(CropTaxonomy::subtypeSlugs())->toHaveCount(count($expected));
});

it('profiles every existing catalog crop', function () {
    $slugs = [
        'sweet-potato', 'peanut', 'watermelon', 'cassava', 'lowland-rice',
        'sugarcane', 'taro', 'red-onion', 'tomato', 'eggplant',
        'ampalaya', 'corn', 'chili', 'strawberry', 'cabbage',
        'pineapple', 'sunflower', 'sorghum', 'coconut', 'mungbean',
        'sitaw', 'okra', 'squash', 'ginger', 'garlic',
        'cucumber', 'malunggay', 'carrot', 'potato', 'bell-pepper',
        'mustard', 'shallot', 'sayote', 'kangkong', 'dill',
        'lemongrass', 'basil',
    ];

    foreach ($slugs as $slug) {
        expect(CropTaxonomy::profile($slug))->not->toBeNull("missing profile: {$slug}");
    }
});

it('gives every selectable subtype at least three crops', function () {
    foreach (CropTaxonomy::subtypeSlugs() as $subtype) {
        expect(count(CropTaxonomy::cropsForSubtype($subtype)))->toBeGreaterThanOrEqual(3, "subtype {$subtype}");
    }
});

it('completes every operational field on every profile', function () {
    $required = [
        'name', 'type', 'subtype', 'family', 'growth_cycle', 'cultivation',
        'light', 'water', 'soil_need', 'harvest', 'seasons',
    ];

    foreach (CropTaxonomy::crops() as $slug => $profile) {
        foreach ($required as $key) {
            expect($profile[$key] ?? null)->not->toBeNull("{$slug}.{$key}")
                ->and($profile[$key])->not->toBe([]);
        }

        expect($profile['growth_cycle'])->toBeIn(['short', 'mid', 'long'], $slug)
            ->and($profile['water'])->toBeIn(['low', 'moderate', 'high'], $slug)
            ->and($profile['soil_need'])->toBeIn(['heavy', 'light', 'builder'], $slug)
            ->and($profile['harvest'])->toBeIn(['single', 'continuous'], $slug);
    }
});

it('maps months to wet and dry seasons', function () {
    expect(CropTaxonomy::seasonForMonth(1))->toBe('dry')
        ->and(CropTaxonomy::seasonForMonth(6))->toBe('wet')
        ->and(CropTaxonomy::seasonForMonth(10))->toBe('wet')
        ->and(CropTaxonomy::seasonForMonth(12))->toBe('dry');
});

it('matches free-text crop names to catalog slugs', function () {
    expect(CropTaxonomy::matchSlug('Calamansi'))->toBe('calamansi')
        ->and(CropTaxonomy::matchSlug('Rice (Jasmine)'))->toBe('lowland-rice')
        ->and(CropTaxonomy::matchSlug('Tomatoes'))->toBe('tomato')
        ->and(CropTaxonomy::matchSlug('String Beans'))->toBe('sitaw')
        ->and(CropTaxonomy::matchSlug('Red Creole Onions'))->toBe('red-onion')
        ->and(CropTaxonomy::matchSlug('Carrots'))->toBe('carrot')
        ->and(CropTaxonomy::matchSlug('White Potato'))->toBe('potato')
        ->and(CropTaxonomy::matchSlug('Spaceship'))->toBeNull();
});

it('nests subtypes with crop counts in the selection tree', function () {
    $tree = CropTaxonomy::tree();

    expect($tree)->toHaveKeys(['vegetable', 'fruit', 'field_crop'])
        ->and($tree['vegetable']['subtypes']['fruiting']['crops'])->toContain('tomato')
        ->and($tree['vegetable']['subtypes']['fruiting']['crop_count'])
        ->toBe(count(CropTaxonomy::cropsForSubtype('fruiting')));
});
