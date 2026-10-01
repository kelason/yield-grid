<?php

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Jobs\GenerateAiPriceEstimateJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.gemini.key', 'test-gemini-key');
    config()->set('services.ai_estimates.enabled', true);
});

function fakeGeminiEstimate(array $tiers): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'is_crop' => true,
                        'display_name' => 'Rice',
                        'tiers' => $tiers,
                    ]),
                ]]],
            ]],
        ]),
    ]);
}

it('stores AI estimated tiers for an unknown crop', function () {
    fakeGeminiEstimate(['farmgate' => 23.00, 'retail' => 48.50]);

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'rice');

    $rows = CropReferencePrice::where('crop_slug', 'rice')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('source')->unique()->all())->toBe([PriceSource::AI_ESTIMATE]);
});

it('makes the next guide lookup return the AI estimate', function () {
    fakeGeminiEstimate(['farmgate' => 23.00, 'retail' => 48.50]);
    GenerateAiPriceEstimateJob::dispatchSync('rice', 'rice');

    $this->getJson('/api/v1/market/prices/guide?crop=rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_estimate', true)
        ->assertJsonPath('tiers.retail.source', PriceSource::AI_ESTIMATE->value);
});

it('stores nothing when AI rejects a non-crop', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode(['is_crop' => false]),
                ]]],
            ]],
        ]),
    ]);

    GenerateAiPriceEstimateJob::dispatchSync('1231sadasd', '1231sadasd');

    expect(CropReferencePrice::count())->toBe(0);
});

it('skips the AI call without an API key', function () {
    config()->set('services.gemini.key', '');
    Http::fake();

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'rice');

    Http::assertNothingSent();
    expect(CropReferencePrice::count())->toBe(0);
});

it('refreshes expired AI estimates with a new daily row', function () {
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 40.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->subDay()->toDateString(),
    ]);
    fakeGeminiEstimate(['retail' => 48.50]);

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'rice');

    expect(CropReferencePrice::where('crop_slug', 'rice')->where('observed_at', now()->toDateString())->count())->toBe(1);
});

it('skips fresh same-day AI estimates', function () {
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 48.50,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->toDateString(),
    ]);
    Http::fake();

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'rice');

    Http::assertNothingSent();
});

it('skips when estimates are disabled', function () {
    config()->set('services.ai_estimates.enabled', false);
    Http::fake();

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'rice');

    Http::assertNothingSent();
    expect(CropReferencePrice::count())->toBe(0);
});

it('estimates a crop that already has DA rows', function () {
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
    fakeGeminiEstimate(['retail' => 48.50]);

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'Rice');

    expect(CropReferencePrice::where('crop_slug', 'rice')->where('source', PriceSource::AI_ESTIMATE)->count())->toBe(1);
});

it('estimates a resolved slug shadowed by an alias with data', function () {
    CropPriceAlias::create([
        'alias_slug' => 'rice',
        'crop_slug' => 'rice-lowland-paddy',
        'source' => CropPriceAlias::SOURCE_AI,
        'verified' => false,
    ]);
    CropReferencePrice::create([
        'crop_slug' => 'rice-lowland-paddy',
        'crop_display_name' => 'Rice (Lowland Paddy)',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 50.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->toDateString(),
    ]);
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
    fakeGeminiEstimate(['retail' => 48.50]);

    GenerateAiPriceEstimateJob::dispatchSync('rice', 'Rice');

    expect(CropReferencePrice::where('crop_slug', 'rice')->where('source', PriceSource::AI_ESTIMATE)->count())->toBe(1);
});

it('skips an unresolved slug that already resolves through an alias', function () {
    CropPriceAlias::create([
        'alias_slug' => 'dragonfruit',
        'crop_slug' => 'rice',
        'source' => CropPriceAlias::SOURCE_AI,
        'verified' => false,
    ]);
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
    Http::fake();

    GenerateAiPriceEstimateJob::dispatchSync('dragonfruit', 'dragonfruit');

    Http::assertNothingSent();
    expect(CropReferencePrice::where('crop_slug', 'dragonfruit')->count())->toBe(0);
});
