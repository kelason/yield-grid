<?php

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\DemandStatus;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
    $this->buyer = User::factory()->buyer()->create();
    $this->demand = Helper::makeDemand($this->buyer);
    $this->farmer = User::factory()->farmer()->create();
    Helper::makeAddress($this->farmer);
    $this->offerUrl = "/api/v1/demands/{$this->demand->id}/offers";
});

it('accepts offer quantity at the min and max', function () {
    $bigDemand = Helper::makeDemand($this->buyer, null, [
        'quantity_kg' => 1000000,
        'remaining_quantity_kg' => 1000000,
        'target_price_per_kg' => 1,
        'total_budget' => 1000000,
    ]);

    $this->actingAs($this->farmer)->postJson("/api/v1/demands/{$bigDemand->id}/offers", [
        'quantity_kg' => MarketplaceConstants::OFFER_QUANTITY_MIN_KG,
        'price_per_kg' => 1,
    ])->assertCreated();

    $secondFarmer = User::factory()->farmer()->create();
    Helper::makeAddress($secondFarmer);

    $this->actingAs($secondFarmer)->postJson("/api/v1/demands/{$bigDemand->id}/offers", [
        'quantity_kg' => MarketplaceConstants::OFFER_QUANTITY_MAX_KG,
        'price_per_kg' => 1,
    ])->assertCreated();
});

it('rejects offer quantity outside the min and max', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 0,
        'price_per_kg' => 44,
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);

    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => MarketplaceConstants::OFFER_QUANTITY_MAX_KG + 1,
        'price_per_kg' => 44,
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('accepts offer price at the min and max', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => MarketplaceConstants::OFFER_PRICE_MIN,
    ])->assertCreated();

    $secondFarmer = User::factory()->farmer()->create();
    Helper::makeAddress($secondFarmer);

    // 0.01 x 99,999,999 = 999,999.99 sits under the order total cap.
    $this->actingAs($secondFarmer)->postJson($this->offerUrl, [
        'quantity_kg' => 0.01,
        'price_per_kg' => MarketplaceConstants::OFFER_PRICE_MAX,
    ])->assertCreated();
});

it('rejects offer price outside the min and max', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => 0,
    ])->assertStatus(422)->assertJsonValidationErrors(['price_per_kg']);

    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => MarketplaceConstants::OFFER_PRICE_MAX + 1,
    ])->assertStatus(422)->assertJsonValidationErrors(['price_per_kg']);
});

it('accepts an offer message at the max length', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
        'message' => str_repeat('a', MarketplaceConstants::OFFER_MESSAGE_MAX_LENGTH),
    ])->assertCreated();
});

it('rejects an offer message one character over the max', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
        'message' => str_repeat('a', MarketplaceConstants::OFFER_MESSAGE_MAX_LENGTH + 1),
    ])->assertStatus(422)->assertJsonValidationErrors(['message']);
});

it('rejects an offer whose total exceeds the order maximum', function () {
    $bigDemand = Helper::makeDemand($this->buyer, null, [
        'quantity_kg' => 1000000,
        'remaining_quantity_kg' => 1000000,
        'target_price_per_kg' => 1,
        'total_budget' => 1000000,
    ]);

    $this->actingAs($this->farmer)->postJson("/api/v1/demands/{$bigDemand->id}/offers", [
        'quantity_kg' => 1000000,
        'price_per_kg' => 10000,
    ])->assertStatus(422)->assertJsonValidationErrors(['quantity_kg']);
});

it('rejects an offer above the remaining quantity with conflict', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 601,
        'price_per_kg' => 44,
    ])->assertStatus(409);
});

it('rejects an offer on a closed demand with conflict', function () {
    $this->demand->update(['status' => DemandStatus::CANCELLED]);

    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
    ])->assertStatus(409);
});

it('rejects a duplicate active offer from the same farmer with conflict', function () {
    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
    ])->assertCreated();

    $this->actingAs($this->farmer)->postJson($this->offerUrl, [
        'quantity_kg' => 100,
        'price_per_kg' => 44,
    ])->assertStatus(409);
});
