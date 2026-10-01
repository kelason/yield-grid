<?php

use App\Constants\MarketplaceConstants;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
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
    $this->plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $this->payload = [
        'title' => '500kg Jasmine Rice',
        'description' => 'Fresh harvest',
        'quantity_kg' => 500,
        'price_per_kg' => 50,
        'estimated_harvest_date' => now()->addDays(30)->format('Y-m-d'),
        'expiry_date' => now()->addDays(15)->format('Y-m-d'),
    ];
    $this->makeRecommendation = function (): CropRecommendation {
        return CropRecommendation::create([
            'plot_id' => $this->plot->id,
            'status' => RecommendationStatus::ACCEPTED,
            'crop_name' => 'Jasmine Rice',
            'projected_yield' => 500,
            'confidence_score' => 90,
            'reasoning' => 'Good soil',
        ]);
    };
});

it('accepts a contract title at the max length', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'title' => str_repeat('a', MarketplaceConstants::CONTRACT_TITLE_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a contract title one character over the max', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'title' => str_repeat('a', MarketplaceConstants::CONTRACT_TITLE_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('accepts a contract description at the max length', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'description' => str_repeat('a', MarketplaceConstants::CONTRACT_DESCRIPTION_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a contract description one character over the max', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'description' => str_repeat('a', MarketplaceConstants::CONTRACT_DESCRIPTION_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);
});

it('accepts contract quantity at the min and max', function () {
    $first = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$first->id}/publish", array_merge($this->payload, [
        'quantity_kg' => MarketplaceConstants::CONTRACT_QUANTITY_MIN_KG,
    ]))->assertCreated();

    $second = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$second->id}/publish", array_merge($this->payload, [
        'quantity_kg' => MarketplaceConstants::CONTRACT_QUANTITY_MAX_KG,
        'price_per_kg' => 0.01,
    ]))->assertCreated();
});

it('rejects contract quantity outside the min and max', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'quantity_kg' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'quantity_kg' => MarketplaceConstants::CONTRACT_QUANTITY_MAX_KG + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts contract price at the min and max', function () {
    $first = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$first->id}/publish", array_merge($this->payload, [
        'price_per_kg' => MarketplaceConstants::CONTRACT_PRICE_MIN,
    ]))->assertCreated();

    $second = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$second->id}/publish", array_merge($this->payload, [
        'quantity_kg' => 1,
        'price_per_kg' => MarketplaceConstants::CONTRACT_PRICE_MAX,
    ]))->assertCreated();
});

it('rejects contract price outside the min and max', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'price_per_kg' => 0,
    ]))->assertStatus(422)->assertJsonValidationErrors(['price_per_kg']);

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'price_per_kg' => MarketplaceConstants::CONTRACT_PRICE_MAX + 1,
    ]))->assertStatus(422)->assertJsonValidationErrors(['price_per_kg']);
});

it('rejects a contract whose total exceeds the order maximum', function () {
    $recommendation = ($this->makeRecommendation)();

    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'quantity_kg' => 99999999,
        'price_per_kg' => 99999999,
    ]))->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts a contract whose total sits just under the order maximum', function () {
    $recommendation = ($this->makeRecommendation)();

    // 99,999,999 x 100 = 9,999,999,900 sits under the cap.
    $this->actingAs($this->farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", array_merge($this->payload, [
        'quantity_kg' => 99999999,
        'price_per_kg' => 100,
    ]))->assertCreated();
});
