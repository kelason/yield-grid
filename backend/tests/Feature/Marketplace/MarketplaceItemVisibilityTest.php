<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $this->farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $this->recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
});

function makeListing(int $farmerId, ContractStatus $status): HarvestListing
{
    return HarvestListing::create([
        'farmer_id' => $farmerId,
        'title' => 'Fresh rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
        'status' => $status,
    ]);
}

it('shows an available listing to guests', function () {
    $listing = makeListing($this->farmer->id, ContractStatus::AVAILABLE);

    $this->getJson("/api/v1/market/items/listing/{$listing->id}")->assertOk();
});

it('hides a sold listing from public item detail', function () {
    $listing = makeListing($this->farmer->id, ContractStatus::SOLD);

    $this->getJson("/api/v1/market/items/listing/{$listing->id}")->assertNotFound();
});

it('hides a sold contract from public item detail', function () {
    $contract = ForwardContract::factory()->sold()->create([
        'farmer_id' => $this->farmer->id,
        'crop_recommendation_id' => $this->recommendation->id,
    ]);

    $this->getJson("/api/v1/market/items/contract/{$contract->id}")->assertNotFound();
});
