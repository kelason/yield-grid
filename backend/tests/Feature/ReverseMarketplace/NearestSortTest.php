<?php

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\HarvestListing;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
});

it('sorts demands nearest-first with distances', function () {
    // Viewer (and near demand) in Makati; far demand in Davao.
    $buyer = User::factory()->buyer()->create();
    $nearAddress = Helper::makeAddress($buyer, ['latitude' => 14.551, 'longitude' => 121.031]);
    $near = Helper::makeDemand($buyer, $nearAddress, ['title' => 'Near demand']);

    $farBuyer = User::factory()->buyer()->create();
    $farAddress = Helper::makeAddress($farBuyer, [
        'latitude' => 7.1907,
        'longitude' => 125.4553,
        'region_code' => Helper::REGION_CALABARZON,
        'province_code' => Helper::PROVINCE_CAVITE,
        'city_municipality_code' => Helper::CITY_DASMARIAS,
        'barangay_code' => Helper::BARANGAY_ZONE,
    ]);
    $far = Helper::makeDemand($farBuyer, $farAddress, ['title' => 'Far demand']);

    $response = $this->getJson('/api/v1/market/demands?sort=nearest&lat=14.551&lng=121.031');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($near->id);
    expect($response->json('data.1.id'))->toBe($far->id);
    expect($response->json('data.0.distance_m'))->toBeLessThan($response->json('data.1.distance_m'));
});

it('falls back to area ranking when the viewer has no coordinates', function () {
    $buyer = User::factory()->buyer()->create();
    Helper::makeAddress($buyer, ['latitude' => null, 'longitude' => null]);

    $sameCityBuyer = User::factory()->buyer()->create();
    $sameCity = Helper::makeDemand($sameCityBuyer, null, ['title' => 'Same city demand']);

    $otherBuyer = User::factory()->buyer()->create();
    Helper::makeDemand($otherBuyer, Helper::makeAddress($otherBuyer, [
        'latitude' => null,
        'longitude' => null,
        'region_code' => Helper::REGION_CALABARZON,
        'province_code' => Helper::PROVINCE_CAVITE,
        'city_municipality_code' => Helper::CITY_DASMARIAS,
        'barangay_code' => Helper::BARANGAY_ZONE,
    ]), ['title' => 'Other area demand']);

    $response = $this->actingAs($buyer)->getJson('/api/v1/market/demands?sort=nearest');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($sameCity->id);
});

it('sorts the forward marketplace nearest-first', function () {
    $buyer = User::factory()->buyer()->create();
    Helper::makeAddress($buyer, ['latitude' => 14.551, 'longitude' => 121.031]);

    $nearFarmer = User::factory()->farmer()->create();
    Helper::makeAddress($nearFarmer, ['latitude' => 14.552, 'longitude' => 121.032]);
    $nearListing = HarvestListing::create([
        'farmer_id' => $nearFarmer->id,
        'title' => 'Near rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 50,
        'total_price' => 5000,
        'currency' => 'PHP',
        'estimated_harvest_date' => now()->addDays(10)->toDateString(),
        'expiry_date' => now()->addDays(20)->toDateString(),
        'status' => ContractStatus::AVAILABLE,
        'is_harvest_available' => true,
    ]);

    $farFarmer = User::factory()->farmer()->create();
    Helper::makeAddress($farFarmer, ['latitude' => 7.1907, 'longitude' => 125.4553]);
    $farListing = HarvestListing::create([
        'farmer_id' => $farFarmer->id,
        'title' => 'Far rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 50,
        'total_price' => 5000,
        'currency' => 'PHP',
        'estimated_harvest_date' => now()->addDays(10)->toDateString(),
        'expiry_date' => now()->addDays(20)->toDateString(),
        'status' => ContractStatus::AVAILABLE,
        'is_harvest_available' => true,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/market/contracts?sort=nearest&crop=rice');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($nearListing->id);
    expect($response->json('data.1.id'))->toBe($farListing->id);
    expect($response->json('data.0.distance_m'))->not->toBeNull();
});
