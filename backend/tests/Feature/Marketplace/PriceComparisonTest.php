<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Models\CropReferencePrice;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Jobs\GenerateAiPriceEstimateJob;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
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

function createRiceListing(): void
{
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
}

it('returns every price source side by side in precedence order', function () {
    createRiceListing();
    CropReferencePrice::create([
        'crop_slug' => 'rice',
        'crop_display_name' => 'Rice',
        'tier' => PriceTier::RETAIL,
        'price_per_kg' => 45.00,
        'currency' => 'PHP',
        'region_code' => null,
        'market_name' => 'AI estimate',
        'source' => PriceSource::AI_ESTIMATE,
        'observed_at' => now()->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/market/prices/guide/compare?crop=rice')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('resolved_from', 'yieldgrid')
        ->assertJsonPath('crop_slug', 'rice')
        ->assertJsonPath('sections.yieldgrid.available', true)
        ->assertJsonPath('sections.yieldgrid.listings_count', 1)
        ->assertJsonPath('sections.da.available', true)
        ->assertJsonPath('sections.da.tiers.retail.source', PriceSource::DA_BANTAY_PRESYO->value)
        ->assertJsonPath('sections.ai.available', true)
        ->assertJsonPath('sections.ai.pending', false);

    expect(array_keys($response->json('sections')))->toBe(['yieldgrid', 'da', 'ai']);
    expect($response->json('sections.yieldgrid.price_per_kg'))->toEqual(30.00);
    expect($response->json('sections.da.price_per_kg'))->toEqual(24.50);
    expect($response->json('sections.da.tiers.retail.price_per_kg'))->toEqual(50.00);
    expect($response->json('sections.da.tiers.farmgate.price_per_kg'))->toEqual(24.50);
    expect($response->json('sections.ai.price_per_kg'))->toEqual(45.00);
    expect($response->json('sections.ai.tiers.retail.price_per_kg'))->toEqual(45.00);
});

it('returns only the requested sources', function () {
    $this->getJson('/api/v1/market/prices/guide/compare?crop=rice&sources[]=da')
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('resolved_from', 'da')
        ->assertJsonPath('sections.da.available', true)
        ->assertJsonMissingPath('sections.yieldgrid')
        ->assertJsonMissingPath('sections.ai');
});

it('reports an unknown crop with a pending AI section', function () {
    $this->getJson('/api/v1/market/prices/guide/compare?crop=1231sadasd')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('reason', 'unknown_crop')
        ->assertJsonPath('message', "No estimated price for '1231sadasd' yet.")
        ->assertJsonPath('resolved_from', null)
        ->assertJsonPath('pending', true)
        ->assertJsonPath('sections.yieldgrid.available', false)
        ->assertJsonPath('sections.da.available', false)
        ->assertJsonPath('sections.ai.available', false)
        ->assertJsonPath('sections.ai.pending', true);
});

it('marks the AI section pending while estimates generate', function () {
    Queue::fake();

    $this->getJson('/api/v1/market/prices/guide/compare?crop=rice&sources[]=ai')
        ->assertOk()
        ->assertJsonPath('available', false)
        ->assertJsonPath('sections.ai.available', false)
        ->assertJsonPath('sections.ai.pending', true);

    Queue::assertPushed(GenerateAiPriceEstimateJob::class);
});

it('rejects invalid sources', function () {
    $this->getJson('/api/v1/market/prices/guide/compare?crop=rice&sources[]=nasa')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sources.0']);
});

it('logs YieldGrid and DA section completion with latency', function () {
    Log::spy();

    $this->getJson('/api/v1/market/prices/guide/compare?crop=rice')->assertOk();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context): bool => $message === 'Crop price YieldGrid section completed'
            && isset($context['latency_ms']) && ($context['available'] ?? null) === false && ($context['listings'] ?? null) === 0)
        ->once();
    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context): bool => $message === 'Crop price DA section completed'
            && isset($context['latency_ms']) && ($context['available'] ?? null) === true && ($context['tiers'] ?? null) === 2)
        ->once();
});

it('excludes hidden contracts and listings from marketplace price sampling', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $farmer = User::factory()->farmer()->create();
    $action = app(ModerateMarketplaceContentAction::class);

    HarvestListing::create([
        'farmer_id' => $farmer->id,
        'title' => 'Visible rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);

    $hiddenListing = HarvestListing::create([
        'farmer_id' => $farmer->id,
        'title' => 'Hidden rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 1000.00,
        'total_price' => 100000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);
    $action->execute($admin, ReportTargetType::LISTING, (string) $hiddenListing->id, true, 'spam pricing');

    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Price Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Price Plot', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
    $hiddenContract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 1000.00,
        'total_price' => 100000.00,
    ]);
    $action->execute($admin, ReportTargetType::CONTRACT, (string) $hiddenContract->id, true, 'spam pricing');

    $average = app(CropReferencePriceRepositoryInterface::class)->marketplaceAverage('rice', 30);

    expect($average)->toBe(['price' => 30.0, 'count' => 1]);
});

it('logs and isolates a failing price source instead of failing the request', function () {
    Log::spy();
    Queue::fake();

    $this->mock(CropReferencePriceRepositoryInterface::class, function ($mock) {
        $mock->shouldReceive('cropCatalog')->andReturn(['rice' => 'Rice']);
        $mock->shouldReceive('latestBySlugAndRegion')->andThrow(new RuntimeException('db down'));
        $mock->shouldReceive('marketplaceAverage')->andReturn(['price' => 30.00, 'count' => 2]);
        $mock->shouldReceive('aiEstimatesBySlug')->andReturn(collect());
    });

    $this->getJson('/api/v1/market/prices/guide/compare?crop=rice')
        ->assertOk()
        ->assertJsonPath('sections.yieldgrid.available', true)
        ->assertJsonPath('sections.da.available', false)
        ->assertJsonPath('sections.ai.available', false);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context): bool => $message === 'Crop price reference lookup failed'
            && ($context['slug'] ?? null) === 'rice')
        ->once();
    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message): bool => $message === 'Crop price YieldGrid section completed')
        ->once();
});
