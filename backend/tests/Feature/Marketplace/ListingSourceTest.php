<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function listingPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Manual Rice',
        'description' => 'Fresh harvest',
        'crop_name' => 'Rice',
        'quantity_kg' => 1000,
        'price_per_kg' => 45.5,
        'estimated_harvest_date' => now()->addDays(20)->format('Y-m-d'),
        'shelf_life_days' => 180,
        'is_harvest_available' => false,
    ], $overrides);
}

function indexItemFor(int $listingId): ?array
{
    $data = test()->getJson('/api/v1/market/contracts')->assertOk()->json('data');

    return collect($data)->firstWhere('id', $listingId);
}

it('creates a listing linked to the farmers own farm and plot', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['farm_id' => $farm->id, 'plot_id' => $plot->id]))->assertCreated();

    expect(HarvestListing::firstWhere('farmer_id', $farmer->id)->farm_id)->toBe($farm->id)
        ->and(HarvestListing::firstWhere('farmer_id', $farmer->id)->plot_id)->toBe($plot->id);
});

it('refuses a listing linked to another farmers farm', function () {
    $farmer = User::factory()->farmer()->create();
    $otherFarm = Farm::factory()->create();

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['farm_id' => $otherFarm->id]))->assertUnprocessable();

    expect(HarvestListing::count())->toBe(0);
});

it('refuses a plot from a different farm than the linked farm', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id]);
    $otherPlot = Plot::factory()->create();

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['farm_id' => $farm->id, 'plot_id' => $otherPlot->id]))->assertUnprocessable();

    expect(HarvestListing::count())->toBe(0);
});

it('refuses a plot without a farm', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['plot_id' => $plot->id]))->assertUnprocessable();

    expect(HarvestListing::count())->toBe(0);
});

it('flags index listings from a verified farm and plot', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id, 'verification_status' => VerificationStatus::VERIFIED]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id, 'verification_status' => VerificationStatus::VERIFIED]);

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['farm_id' => $farm->id, 'plot_id' => $plot->id]))->assertCreated();
    $listing = HarvestListing::firstWhere('farmer_id', $farmer->id);

    expect(indexItemFor($listing->id)['is_from_verified_farm'])->toBeTrue();
    $this->getJson("/api/v1/market/items/listing/{$listing->id}")
        ->assertOk()->assertJsonPath('data.is_from_verified_farm', true);
});

it('does not flag listings from an unverified plot on a verified farm', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id, 'verification_status' => VerificationStatus::VERIFIED]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['farm_id' => $farm->id, 'plot_id' => $plot->id]))->assertCreated();
    $listing = HarvestListing::firstWhere('farmer_id', $farmer->id);

    expect(indexItemFor($listing->id)['is_from_verified_farm'])->toBeFalse();
});

it('does not flag listings without a linked farm', function () {
    $farmer = User::factory()->farmer()->create();

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings', listingPayload())->assertCreated();
    $listing = HarvestListing::firstWhere('farmer_id', $farmer->id);

    expect(indexItemFor($listing->id)['is_from_verified_farm'])->toBeFalse();
});

it('never flags contracts as verified farm produce', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
    ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    $data = $this->getJson('/api/v1/market/contracts')->assertOk()->json('data');
    $contract = collect($data)->firstWhere('type', 'contract');

    expect($contract)->not->toBeNull()
        ->and($contract['is_from_verified_farm'])->toBeFalse();
});

it('keeps the listing alive without a badge after its farm is deleted', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id, 'verification_status' => VerificationStatus::VERIFIED]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id, 'verification_status' => VerificationStatus::VERIFIED]);

    $this->actingAs($farmer)->postJson('/api/v1/farmer/listings',
        listingPayload(['farm_id' => $farm->id, 'plot_id' => $plot->id]))->assertCreated();
    $listing = HarvestListing::firstWhere('farmer_id', $farmer->id);
    expect(indexItemFor($listing->id)['is_from_verified_farm'])->toBeTrue();

    $farm->delete();

    $this->getJson("/api/v1/market/items/listing/{$listing->id}")
        ->assertOk()->assertJsonPath('data.is_from_verified_farm', false);
});
