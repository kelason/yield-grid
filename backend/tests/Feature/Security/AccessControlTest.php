<?php

// OWASP A01:2021 — Broken Access Control.

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createAccessControlFarmerWithContract(): array
{
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
    $contract = ForwardContract::factory()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    return [$farmer, $farm, $contract];
}

it('rejects unauthenticated requests to protected endpoints', function () {
    $this->getJson('/api/v1/farms')->assertUnauthorized();
    $this->getJson('/api/v1/buyer/purchases')->assertUnauthorized();
    $this->postJson('/api/v1/farms', ['name' => 'Intruder Farm'])->assertUnauthorized();
    $this->getJson('/api/v1/user')->assertUnauthorized();
});

it('prevents one farmer from viewing another farmer contract (IDOR)', function () {
    [, , $contract] = createAccessControlFarmerWithContract();
    $otherFarmer = User::factory()->farmer()->create();

    $this->actingAs($otherFarmer)
        ->getJson("/api/v1/farmer/contracts/{$contract->id}")
        ->assertForbidden();
});

it('prevents one farmer from cancelling another farmer contract (IDOR)', function () {
    [, , $contract] = createAccessControlFarmerWithContract();
    $otherFarmer = User::factory()->farmer()->create();

    $this->actingAs($otherFarmer)
        ->patchJson("/api/v1/farmer/contracts/{$contract->id}/cancel")
        ->assertForbidden();

    expect($contract->fresh()->status)->toBe($contract->status);
});

it('prevents one farmer from listing plots on another farmer farm (IDOR)', function () {
    [, $farm] = createAccessControlFarmerWithContract();
    $otherFarmer = User::factory()->farmer()->create();

    $this->actingAs($otherFarmer)
        ->getJson("/api/v1/farms/{$farm->id}/plots")
        ->assertForbidden();
});

it('prevents one farmer from creating a plot on another farmer farm (IDOR)', function () {
    [, $farm] = createAccessControlFarmerWithContract();
    $otherFarmer = User::factory()->farmer()->create();

    $this->actingAs($otherFarmer)
        ->postJson("/api/v1/farms/{$farm->id}/plots", [
            'name' => 'Intruder Field',
            'soil_type' => 'loamy',
            'coordinates' => [[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('plots', ['name' => 'Intruder Field']);
});

it('prevents one buyer from viewing another buyer purchase (IDOR)', function () {
    [, , $contract] = createAccessControlFarmerWithContract();
    $buyer = User::factory()->buyer()->create();
    $purchase = Purchase::factory()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
    ]);
    $otherBuyer = User::factory()->buyer()->create();

    $this->actingAs($otherBuyer)
        ->getJson("/api/v1/buyer/purchases/{$purchase->id}")
        ->assertForbidden();
});

it('prevents buyers from approving cash payments', function () {
    [, , $contract] = createAccessControlFarmerWithContract();
    $purchase = Purchase::factory()->create(['forward_contract_id' => $contract->id]);
    $buyer = User::factory()->buyer()->create();

    $this->actingAs($buyer)
        ->postJson("/api/v1/farmer/purchases/{$purchase->id}/approve", ['type' => 'full'])
        ->assertForbidden();
});
