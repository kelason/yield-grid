<?php

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Jobs\LearnCropAliasJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.gemini.key', 'test-gemini-key');
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 50.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'National Average',
        'source' => PriceSource::DA_BANTAY_PRESYO,
        'observed_at' => now()->toDateString(),
    ]);
});

function fakeGeminiMatch(?string $match, int $confidence): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode(['match' => $match, 'confidence' => $confidence]),
                ]]],
            ]],
        ]),
    ]);
}

it('learns an AI-suggested alias for a future match', function () {
    fakeGeminiMatch('rice', 95);

    LearnCropAliasJob::dispatchSync('bigas', 'bigas');

    expect(CropPriceAlias::where('alias_slug', 'bigas')->first())
        ->not->toBeNull()
        ->crop_slug->toBe('rice')
        ->source->toBe('ai')
        ->verified->toBeFalse();
});

it('marks the next guide lookup as ai_corrected', function () {
    fakeGeminiMatch('rice', 95);
    LearnCropAliasJob::dispatchSync('bigas', 'bigas');

    $this->getJson('/api/v1/market/prices/guide?crop=bigas')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('crop_slug', 'rice')
        ->assertJsonPath('match_type', 'ai_corrected');
});

it('ignores low-confidence AI suggestions', function () {
    fakeGeminiMatch('rice', 40);

    LearnCropAliasJob::dispatchSync('xyzabc', 'xyzabc');

    expect(CropPriceAlias::where('alias_slug', 'xyzabc')->first())->toBeNull();
});

it('stores nothing when AI rejects gibberish', function () {
    fakeGeminiMatch(null, 0);

    LearnCropAliasJob::dispatchSync('1231sadasd', '1231sadasd');

    expect(CropPriceAlias::where('alias_slug', '1231sadasd')->first())->toBeNull();

    $this->getJson('/api/v1/market/prices/guide?crop=1231sadasd')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'unknown_crop');
});

it('skips the AI call without an API key', function () {
    config()->set('services.gemini.key', '');
    Http::fake();

    LearnCropAliasJob::dispatchSync('bigas', 'bigas');

    Http::assertNothingSent();
    expect(CropPriceAlias::where('alias_slug', 'bigas')->first())->toBeNull();
});
