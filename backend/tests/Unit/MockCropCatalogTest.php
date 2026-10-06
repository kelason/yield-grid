<?php

use App\Domain\CropRecommendation\Catalog\MockCropCatalog;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;

function tarlacLocation(): array
{
    return ['city' => 'Tarlac', 'state' => 'Tarlac', 'country' => 'Philippines'];
}

it('returns ten shaped recommendations', function () {
    $recs = MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation());

    expect($recs)->toHaveCount(10);

    foreach ($recs as $rec) {
        expect(array_keys($rec))->toBe([
            'crop_name', 'confidence_score', 'reasoning', 'projected_yield', 'produce_type', 'subtype',
        ]);
    }
});

it('keeps every entry slug wired to the taxonomy', function () {
    foreach (MockCropCatalog::entries() as $entry) {
        expect(CropTaxonomy::profile($entry['slug']))->not->toBeNull("missing taxonomy: {$entry['slug']}");
    }
});

it('covers the taxonomy additions', function () {
    $slugs = array_column(MockCropCatalog::entries(), 'slug');

    expect($slugs)->toHaveCount(51)
        ->and($slugs)->toContain('pechay', 'calamansi', 'banana', 'broccoli', 'mulberry')
        ->and($slugs)->toContain('carrot', 'potato', 'bell-pepper', 'mustard', 'shallot')
        ->and($slugs)->toContain('sayote', 'kangkong', 'dill', 'lemongrass', 'basil');
});

it('fills thin herb pools from the same produce types', function () {
    $recs = MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation(), ['subtypes' => ['herbs']]);
    $names = array_column($recs, 'crop_name');

    expect($recs)->toHaveCount(10)
        ->and(array_slice($names, 0, 3))->toContain('Dill', 'Lemongrass (Tanglad)', 'Sweet Basil (Balanoy)');
});

it('orders deterministically for identical inputs', function () {
    $first = array_column(MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation()), 'crop_name');
    $second = array_column(MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation()), 'crop_name');

    expect($first)->toBe($second);
});

it('fills thin subtype pools from the same produce types', function () {
    $recs = MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation(), ['subtypes' => ['citrus']]);
    $names = array_column($recs, 'crop_name');

    expect($recs)->toHaveCount(10)
        ->and(array_slice($names, 0, 3))->toBe([
            'Calamansi',
            'Pomelo (Suha)',
            'Orange (Dalandan)',
        ]);
});

it('penalizes thirsty crops without irrigation', function () {
    $plain = array_column(MockCropCatalog::recommend(10.0, 'clay', tarlacLocation()), 'crop_name');
    $dry = array_column(
        MockCropCatalog::recommend(10.0, 'clay', tarlacLocation(), ['irrigation' => 'none']),
        'crop_name'
    );

    expect($plain[0])->toBe('Lowland Rice (Palay)')
        ->and(array_slice($dry, 0, 3))->not->toContain('Lowland Rice (Palay)');
});

it('ranks in-season crops higher', function () {
    $dryTop = array_column(
        MockCropCatalog::recommend(0.5, 'silt', tarlacLocation(), ['season' => 'dry']),
        'crop_name'
    );
    $wetTop = array_column(
        MockCropCatalog::recommend(0.5, 'silt', tarlacLocation(), ['season' => 'wet']),
        'crop_name'
    );

    expect($dryTop)->toContain('Red Creole Onions (Sibuyas)')
        ->and(array_search('Red Creole Onions (Sibuyas)', $dryTop))
        ->toBeLessThan(array_search('Red Creole Onions (Sibuyas)', $wetTop) ?: 99);
});

it('drops same-family succession after a cucurbit', function () {
    $plain = array_column(MockCropCatalog::recommend(1.0, 'silt', tarlacLocation()), 'crop_name');
    $rotated = array_column(
        MockCropCatalog::recommend(1.0, 'silt', tarlacLocation(), ['previous_crops' => ['watermelon']]),
        'crop_name'
    );

    expect($plain[0])->toBe('Watermelon (Pakwan)')
        ->and(array_slice($rotated, 0, 3))->not->toContain('Watermelon (Pakwan)');
});

it('lifts heavy feeders after a nitrogen builder', function () {
    $plain = array_column(MockCropCatalog::recommend(1.0, 'silt', tarlacLocation()), 'crop_name');
    $rotated = array_column(
        MockCropCatalog::recommend(1.0, 'silt', tarlacLocation(), ['previous_crops' => ['mungbean']]),
        'crop_name'
    );

    expect($rotated)->toContain('Tomatoes (Kamatis)')
        ->and(array_search('Tomatoes (Kamatis)', $rotated))
        ->toBeLessThan(array_search('Tomatoes (Kamatis)', $plain) ?: 99);
});

it('lifts crops with open buyer demand', function () {
    $plain = array_column(MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation()), 'crop_name');
    $demanded = array_column(
        MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation(), [
            'demands' => [['crop' => 'Calamansi', 'quantity_kg' => 500, 'target_price_per_kg' => 45, 'needed_by' => null]],
        ]),
        'crop_name'
    );

    expect($plain)->not->toContain('Calamansi')
        ->and($demanded)->toContain('Calamansi');
});

it('tags every recommendation with its taxonomy', function () {
    $recs = MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation(), ['subtypes' => ['citrus']]);

    foreach (array_slice($recs, 0, 3) as $rec) {
        expect($rec['produce_type'])->toBe('fruit')
            ->and($rec['subtype'])->toBe('citrus');
    }
});

it('ranks soil-matched staples at the top', function () {
    $clayTop = array_column(MockCropCatalog::recommend(10.0, 'clay', tarlacLocation()), 'crop_name');
    $loamyTop = array_column(MockCropCatalog::recommend(1.0, 'loamy', tarlacLocation()), 'crop_name');

    expect($clayTop)->toContain('Lowland Rice (Palay)')
        ->and($loamyTop)->toBe([
            'Sweet Potato (Camote)',
            'Peanuts (Mani)',
            'Watermelon (Pakwan)',
            'Cassava (Kamoteng Kahoy)',
            'Sugarcane (Tubo)',
            'Yellow Corn (Maize)',
            'Hot Chili (Siling Labuyo)',
            'Sunflowers (Commercial Sunflower)',
            'Mungbean (Munggo / Balatong)',
            'String Beans (Sitaw)',
        ]);
});
