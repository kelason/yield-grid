<?php

use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Infrastructure\Services\DaCropVocabulary;
use Database\Seeders\CropPriceAliasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CropPriceAliasSeeder::class);
});

it('maps DA rice grades to the rice base crop', function () {
    expect(DaCropVocabulary::canonicalSlug('commercial-local-well-milled'))->toBe('rice')
        ->and(DaCropVocabulary::canonicalSlug('commercial-imported-special-blue-tagged'))->toBe('rice')
        ->and(DaCropVocabulary::canonicalSlug('nfa'))->toBe('rice');
});

it('maps variant slugs to their base crop', function () {
    expect(DaCropVocabulary::canonicalSlug('white-potato'))->toBe('potato')
        ->and(DaCropVocabulary::canonicalSlug('banana-lakatan'))->toBe('banana')
        ->and(DaCropVocabulary::canonicalSlug('corn-white'))->toBe('corn')
        ->and(DaCropVocabulary::canonicalSlug('mango-carabao'))->toBe('mango')
        ->and(DaCropVocabulary::canonicalSlug('cabbage-scorpio'))->toBe('cabbage');
});

it('maps bulb and spice variants to their base crop', function () {
    expect(DaCropVocabulary::canonicalSlug('red-onion'))->toBe('onion')
        ->and(DaCropVocabulary::canonicalSlug('red-onion-imported'))->toBe('onion')
        ->and(DaCropVocabulary::canonicalSlug('white-onion'))->toBe('onion')
        ->and(DaCropVocabulary::canonicalSlug('white-onion-imported'))->toBe('onion')
        ->and(DaCropVocabulary::canonicalSlug('garlicimported'))->toBe('garlic')
        ->and(DaCropVocabulary::canonicalSlug('garlicnative'))->toBe('garlic')
        ->and(DaCropVocabulary::canonicalSlug('chili-red'))->toBe('chili')
        ->and(DaCropVocabulary::canonicalSlug('ginger'))->toBe('ginger');
});

it('leaves base crops and unrecognized slugs untouched', function () {
    expect(DaCropVocabulary::canonicalSlug('tomato'))->toBe('tomato')
        ->and(DaCropVocabulary::canonicalSlug('calamansi'))->toBe('calamansi')
        ->and(DaCropVocabulary::canonicalSlug('sitao'))->toBe('sitao')
        ->and(DaCropVocabulary::canonicalSlug('some-new-crop'))->toBe('some-new-crop');
});

it('finds bases from seeded alias targets', function () {
    CropPriceAlias::create(['alias_slug' => 'foo-local', 'crop_slug' => 'foo', 'source' => 'seed', 'verified' => true]);

    // 'foo' is now a known base via alias targets.
    expect(DaCropVocabulary::canonicalSlug('foo-special'))->toBe('foo');
});
