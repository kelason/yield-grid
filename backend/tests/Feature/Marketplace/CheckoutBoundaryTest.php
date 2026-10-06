<?php

use App\Constants\MarketplaceConstants;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->buyer = User::factory()->buyer()->create();
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
    $this->makeContract = function (float $quantityKg): ForwardContract {
        return ForwardContract::factory()->available()->create([
            'farmer_id' => $this->farmer->id,
            'crop_recommendation_id' => $this->recommendation->id,
            'quantity_kg' => $quantityKg,
            'price_per_kg' => 10,
            'total_price' => $quantityKg * 10,
        ]);
    };
});

it('accepts checkout quantity at the min', function () {
    $contract = ($this->makeContract)(MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => MarketplaceConstants::CHECKOUT_QUANTITY_MIN_KG,
        'payment_option' => 'cash',
    ])->assertOk();
});

it('rejects checkout quantity below the min', function () {
    $contract = ($this->makeContract)(15000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => 0,
        'payment_option' => 'cash',
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts checkout quantity at the max', function () {
    $contract = ($this->makeContract)(MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG,
        'payment_option' => 'cash',
    ])->assertOk();
});

it('rejects checkout quantity above the max', function () {
    $contract = ($this->makeContract)(15000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => MarketplaceConstants::CHECKOUT_QUANTITY_MAX_KG + 1,
        'payment_option' => 'cash',
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts a 14000 kg order in a single checkout', function () {
    $contract = ($this->makeContract)(14000);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => 14000,
        'payment_option' => 'cash',
    ])->assertOk();
});

it('rejects checkout quantity above availability with conflict', function () {
    $contract = ($this->makeContract)(100);

    $this->actingAs($this->buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => 500,
        'payment_option' => 'cash',
    ])->assertStatus(409);
});
