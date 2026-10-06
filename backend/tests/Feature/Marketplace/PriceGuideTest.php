<?php

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Models\CropPriceAlias;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Jobs\GenerateAiPriceEstimateJob;
use App\Jobs\LearnCropAliasJob;
use Database\Seeders\CropPriceAliasSeeder;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // The guide dispatches the AI estimate job inline (sync queue). Fake
    // HTTP so no test can reach live Gemini and upsert real estimates.
    Http::fake();

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
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::FARMGATE,
        'price_per_kg' => 24.50,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'National Average',
        'source' => PriceSource::DA_BANTAY_PRESYO,
        'observed_at' => now()->toDateString(),
    ]);
});

it('returns the DA price guide for an exact crop match', function () {
    $response = $this->getJson('/api/v1/market/prices/guide?crop=Rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('crop_slug', 'rice')
        ->assertJsonPath('match_type', 'exact')
        ->assertJsonPath('tiers.retail.source', PriceSource::DA_BANTAY_PRESYO->value)
        ->assertJsonPath('tiers.farmgate.price_per_kg', 24.50)
        ->assertJsonPath('is_estimate', false)
        ->assertJsonPath('is_stale', false)
        ->assertJsonPath('sources_checked', ['yieldgrid', 'da'])
        ->assertJsonPath('resolved_from', 'da');

    // Whole-number prices encode as JSON ints — compare numerically.
    expect($response->json('tiers.retail.price_per_kg'))->toEqual(50.00);
});

it('resolves a known alias to its canonical crop', function () {
    CropPriceAlias::create(['alias_slug' => 'palay', 'crop_slug' => 'rice', 'source' => 'seed', 'verified' => true]);

    $this->getJson('/api/v1/market/prices/guide?crop=palay')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('crop_slug', 'rice')
        ->assertJsonPath('match_type', 'alias')
        ->assertJsonPath('corrected_from', 'palay');
});

it('fuzzy-corrects a misspelled crop name', function () {
    $this->getJson('/api/v1/market/prices/guide?crop=rcie')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('crop_slug', 'rice')
        ->assertJsonPath('match_type', 'fuzzy')
        ->assertJsonPath('corrected_from', 'rcie');
});

it('does not confuse similar names of different crops', function () {
    // potato is two substitutions away from tomato — it must not resolve
    // to tomato prices. Unknown instead, so AI estimates real potato prices.
    CropReferencePrice::create([
        'crop_slug' => 'tomato',
        'crop_display_name' => 'Tomato',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 45.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'National Average',
        'source' => PriceSource::DA_BANTAY_PRESYO,
        'observed_at' => now()->toDateString(),
    ]);

    $this->getJson('/api/v1/market/prices/guide?crop=potato')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'unknown_crop');
});

it('reuses same-day AI estimates without regenerating', function () {
    Queue::fake();

    CropReferencePrice::create([
        'crop_slug' => 'tomato',
        'crop_display_name' => 'Tomato',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 45.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->toDateString(),
    ]);

    $this->getJson('/api/v1/market/prices/guide?crop=tomato')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_estimate', true)
        ->assertJsonPath('is_stale', false)
        ->assertJsonPath('sources_checked', ['yieldgrid', 'da', 'ai'])
        ->assertJsonPath('resolved_from', 'ai');

    Queue::assertNotPushed(GenerateAiPriceEstimateJob::class);
});

it('serves expired AI estimates as stale while refreshing', function () {
    Queue::fake();

    CropReferencePrice::create([
        'crop_slug' => 'tomato',
        'crop_display_name' => 'Tomato',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 45.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->subDay()->toDateString(),
    ]);

    $this->getJson('/api/v1/market/prices/guide?crop=tomato')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_estimate', true)
        ->assertJsonPath('is_stale', true);

    Queue::assertPushed(GenerateAiPriceEstimateJob::class);
});

it('marks unavailable guides as pending generation', function () {
    $this->getJson('/api/v1/market/prices/guide?crop=dragonfruit')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('pending', true);
});

it('declines to guess when two crops are equally close', function () {
    foreach (['apple', 'apply'] as $slug) {
        CropReferencePrice::create([
            'crop_slug' => $slug,
            'crop_display_name' => ucfirst($slug),
            'tier' => PriceTier::RETAIL,
            'price_per_kg' => 45.00,
            'currency' => 'PHP',
            'region_code' => null,
            'market_name' => 'National Average',
            'source' => PriceSource::DA_BANTAY_PRESYO,
            'observed_at' => now()->toDateString(),
        ]);
    }

    // "appl" is one edit from both apple and apply — guessing would risk
    // showing the wrong crop's prices.
    $this->getJson('/api/v1/market/prices/guide?crop=appl')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'unknown_crop');
});

it('resolves every similar crop name to itself, never a neighbor', function () {
    $crops = ['corn', 'tomato', 'potato', 'onion', 'garlic', 'mango', 'banana', 'carrot', 'cabbage'];

    foreach ($crops as $slug) {
        CropReferencePrice::create([
            'crop_slug' => $slug,
            'crop_display_name' => ucfirst($slug),
            'tier' => PriceTier::RETAIL,
            'price_per_kg' => 45.00,
            'currency' => 'PHP',
            'region_code' => null,
            'market_name' => 'National Average',
            'source' => PriceSource::DA_BANTAY_PRESYO,
            'observed_at' => now()->toDateString(),
        ]);
    }

    $this->seed(CropPriceAliasSeeder::class);

    $cases = [
        // [input, expected slug]
        ['corn', 'corn'],
        ['tomato', 'tomato'],
        ['potato', 'potato'],
        ['onion', 'onion'],
        ['garlic', 'garlic'],
        ['mango', 'mango'],
        ['banana', 'banana'],
        ['carrot', 'carrot'],
        ['cabbage', 'cabbage'],
        ['patatas', 'potato'],
        ['potatoes', 'potato'],
        ['potatoe', 'potato'],
        ['tomatoes', 'tomato'],
        ['mangga', 'mango'],
        ['karot', 'carrot'],
        ['kamatis', 'tomato'],
        ['palay', 'rice'],
        ['mais', 'corn'],
        ['sibuyas', 'onion'],
        ['bawang', 'garlic'],
    ];

    foreach ($cases as [$input, $expected]) {
        $this->getJson('/api/v1/market/prices/guide?crop='.urlencode($input))
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('crop_slug', $expected);
    }
});

it('returns unavailable for a nonsense crop and queues AI learning', function () {
    Queue::fake();

    $this->getJson('/api/v1/market/prices/guide?crop=1231sadasd')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'unknown_crop')
        ->assertJsonPath('message', "No estimated price for '1231sadasd' yet.")
        ->assertJsonPath('sources_checked', [])
        ->assertJsonPath('resolved_from', null);

    Queue::assertPushed(LearnCropAliasJob::class);
});

it('reports no estimated price when a known crop has no data anywhere', function () {
    CropReferencePrice::query()->delete();
    CropReferencePrice::create([
        'crop_slug' => 'durian',
        'crop_display_name' => 'Durian',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 90.00,
        'currency' => 'PHP',
        'region_code' => '130000000',
        'market_name' => 'NCR Market',
        'source' => PriceSource::DA_BANTAY_PRESYO,
        'observed_at' => now()->toDateString(),
    ]);

    // National query with only regional rows, no marketplace listings:
    // DA missed, YieldGrid missed, AI estimate dispatched, honest message.
    $this->getJson('/api/v1/market/prices/guide?crop=durian')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'no_price_data')
        ->assertJsonPath('message', "No estimated price for 'Durian' yet.")
        ->assertJsonPath('pending', true)
        ->assertJsonPath('sources_checked', ['yieldgrid', 'da'])
        ->assertJsonPath('resolved_from', null);
});

it('prefers the regional price and falls back to national', function () {
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 55.00,
        'currency' => 'PHP',
        'region_code' => '04',
        'market_name' => 'CALABARZON Market',
        'source' => PriceSource::DA_BANTAY_PRESYO,
        'observed_at' => now()->toDateString(),
    ]);

    $regional = $this->getJson('/api/v1/market/prices/guide?crop=rice&region=04')
        ->assertOk()
        ->assertJsonPath('region_code', '04')
        ->assertJsonPath('region_fallback', false);

    expect($regional->json('tiers.retail.price_per_kg'))->toEqual(55.00);

    $national = $this->getJson('/api/v1/market/prices/guide?crop=rice&region=99')
        ->assertOk()
        ->assertJsonPath('region_fallback', true);

    expect($national->json('tiers.retail.price_per_kg'))->toEqual(50.00);
});

it('flags stale prices instead of hiding them', function () {
    CropReferencePrice::query()->update(['observed_at' => now()->subDays(10)->toDateString()]);

    $this->getJson('/api/v1/market/prices/guide?crop=rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_stale', true);
});

it('requires the crop parameter', function () {
    $this->getJson('/api/v1/market/prices/guide')->assertStatus(422)->assertJsonValidationErrors(['crop']);
});

it('prefers DA rows over AI estimates', function () {
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 99.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/market/prices/guide?crop=rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_estimate', false);

    expect($response->json('tiers.retail.price_per_kg'))->toEqual(50.00);
});

it('prefers the marketplace average over DA rows', function () {
    $farmer = User::factory()->farmer()->create();
    HarvestListing::create([
        'farmer_id' => $farmer->id,
        'title' => 'Fresh rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/market/prices/guide?crop=rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_estimate', true)
        ->assertJsonPath('tiers.estimate.source', PriceSource::MARKETPLACE_AVERAGE->value)
        ->assertJsonPath('sources_checked', ['yieldgrid'])
        ->assertJsonPath('resolved_from', 'yieldgrid');

    expect($response->json('tiers.estimate.price_per_kg'))->toEqual(30.00);
});

it('prefers the marketplace average over AI estimates', function () {
    CropReferencePrice::query()->delete();
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 99.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->toDateString(),
    ]);

    $farmer = User::factory()->farmer()->create();
    HarvestListing::create([
        'farmer_id' => $farmer->id,
        'title' => 'Fresh rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/market/prices/guide?crop=rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('is_estimate', true)
        ->assertJsonPath('tiers.estimate.source', PriceSource::MARKETPLACE_AVERAGE->value)
        ->assertJsonPath('sources_checked', ['yieldgrid'])
        ->assertJsonPath('resolved_from', 'yieldgrid');

    expect($response->json('tiers.estimate.price_per_kg'))->toEqual(30.00);
});

it('dispatches AI estimation for an unknown crop', function () {
    Queue::fake();

    $this->getJson('/api/v1/market/prices/guide?crop=dragonfruit')
        ->assertOk()
        ->assertJsonPath('available', false);

    Queue::assertPushed(GenerateAiPriceEstimateJob::class);
});

it('serves multiple crops in one batch call', function () {
    CropReferencePrice::create([
        'crop_slug' => 'tomato',
        'crop_display_name' => 'Tomato',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 45.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'National Average',
        'source' => PriceSource::DA_BANTAY_PRESYO,
        'observed_at' => now()->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/market/prices/guide/batch?crops[]=rice&crops[]=tomato&crops[]=1231sadasd')
        ->assertOk()
        ->assertJsonPath('guides.rice.available', true)
        ->assertJsonPath('guides.tomato.available', true)
        ->assertJsonPath('guides.1231sadasd.available', false);

    expect($response->json('guides.tomato.tiers.retail.price_per_kg'))->toEqual(45.00);
});
