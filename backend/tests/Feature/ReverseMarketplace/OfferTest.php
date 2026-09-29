<?php

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Shared\Middleware\EnsureUserHasMarketplaceAddress;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
});

it('allows a farmer with an address to submit a partial offer', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);

    $response = $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
        'message' => 'Fresh harvest',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', DemandOfferStatus::PENDING->value);
    expect((float) $response->json('data.total_price'))->toBe(6600.0);
    // Regression: the nested demand serializes without an eager-loaded
    // delivery address and must not leak the buyer's address to the farmer.
    $response->assertJsonPath('data.demand.id', $demand->id);
    $response->assertJsonPath('data.demand.delivery_address', null);

    $this->assertDatabaseHas('crop_demand_offers', [
        'crop_demand_id' => $demand->id,
        'farmer_id' => $farmer->id,
        'quantity_kg' => 150,
    ]);
});

it('blocks offer submission without a saved address', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();

    $response = $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error_code', EnsureUserHasMarketplaceAddress::ERROR_CODE);
});

it('prevents buyers from offering on their own demand', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);

    $this->actingAs($buyer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
    ])->assertForbidden();
});

it('rejects offers above the remaining quantity', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, ['remaining_quantity_kg' => 100]);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);

    $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
    ])->assertStatus(409);
});

it('rejects a second active offer from the same farmer', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);
    Helper::makeOffer($demand, $farmer);

    $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 100,
        'price_per_kg' => 44,
    ])->assertStatus(409);
});

it('accepts offers partially until the demand is fully allocated', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmers = User::factory()->farmer()->count(3)->create();

    $offer1 = Helper::makeOffer($demand, $farmers[0], ['quantity_kg' => 150]);
    $offer2 = Helper::makeOffer($demand, $farmers[1], ['quantity_kg' => 250]);
    $offer3 = Helper::makeOffer($demand, $farmers[2], ['quantity_kg' => 200]);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer1->id}/accept")->assertOk();
    expect((float) $demand->fresh()->remaining_quantity_kg)->toBe(450.0);
    expect($demand->fresh()->status)->toBe(DemandStatus::OPEN);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer2->id}/accept")->assertOk();
    expect((float) $demand->fresh()->remaining_quantity_kg)->toBe(200.0);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer3->id}/accept")->assertOk();
    expect((float) $demand->fresh()->remaining_quantity_kg)->toBe(0.0);
    expect($demand->fresh()->status)->toBe(DemandStatus::FULLY_ALLOCATED);

    // Fully allocated demands accept no further offers.
    $lateFarmer = User::factory()->farmer()->create();
    Helper::makeAddress($lateFarmer);
    $this->actingAs($lateFarmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 10,
        'price_per_kg' => 40,
    ])->assertStatus(409);
});

it('refuses a second acceptance that no longer fits', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, ['quantity_kg' => 600, 'remaining_quantity_kg' => 600]);
    $farmer1 = User::factory()->farmer()->create();
    $farmer2 = User::factory()->farmer()->create();
    $offer1 = Helper::makeOffer($demand, $farmer1, ['quantity_kg' => 600]);
    $offer2 = Helper::makeOffer($demand, $farmer2, ['quantity_kg' => 600]);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer1->id}/accept")->assertOk();
    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer2->id}/accept")->assertStatus(409);
});

it('rejects an offer without touching remaining quantity', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/reject")->assertOk();

    expect($offer->fresh()->status)->toBe(DemandOfferStatus::REJECTED);
    expect((float) $demand->fresh()->remaining_quantity_kg)->toBe(600.0);
});

it('allows a farmer to withdraw a pending offer but not an accepted one', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $pending = Helper::makeOffer($demand, $farmer);
    $accepted = Helper::makeOffer($demand, User::factory()->farmer()->create(), [
        'status' => DemandOfferStatus::ACCEPTED,
    ]);

    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$pending->id}/withdraw")->assertOk();
    expect($pending->fresh()->status)->toBe(DemandOfferStatus::WITHDRAWN);

    $this->actingAs($accepted->farmer)->postJson("/api/v1/farmer/offers/{$accepted->id}/withdraw")
        ->assertStatus(409);
});

it('restores remaining quantity when an accepted unpaid offer is cancelled', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, ['remaining_quantity_kg' => 0, 'status' => DemandStatus::FULLY_ALLOCATED]);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, [
        'quantity_kg' => 600,
        'status' => DemandOfferStatus::ACCEPTED,
    ]);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/cancel")->assertOk();

    expect($offer->fresh()->status)->toBe(DemandOfferStatus::CANCELLED);
    expect((float) $demand->fresh()->remaining_quantity_kg)->toBe(600.0);
    expect($demand->fresh()->status)->toBe(DemandStatus::OPEN);
});

it('refuses to cancel a paid offer', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::PAID]);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/cancel")->assertStatus(409);
});

it('lists a demand offers to its buyer only', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeOffer($demand, $farmer);
    $stranger = User::factory()->buyer()->create();

    $this->actingAs($buyer)->getJson("/api/v1/buyer/demands/{$demand->id}/offers")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($stranger)->getJson("/api/v1/buyer/demands/{$demand->id}/offers")
        ->assertForbidden();
});

it('rejects an offer price above 8 digits', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);

    $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 1,
        'price_per_kg' => 100000000,
    ])->assertStatus(422)->assertJsonValidationErrors('price_per_kg');
});

it('accepts an 8-digit offer price within the order total', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);

    $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 1,
        'price_per_kg' => 99999999,
    ])->assertCreated();
});

it('enforces the 1000-character offer message limit', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);
    $otherFarmer = User::factory()->farmer()->create();
    Helper::makeAddress($otherFarmer);

    $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 1,
        'price_per_kg' => 45,
        'message' => str_repeat('a', 1000),
    ])->assertCreated();

    $this->actingAs($otherFarmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 1,
        'price_per_kg' => 45,
        'message' => str_repeat('b', 1001),
    ])->assertStatus(422)->assertJsonValidationErrors('message');
});

it('rejects an offer whose total exceeds the maximum order total', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, ['quantity_kg' => 1000000, 'remaining_quantity_kg' => 1000000]);
    $farmer = User::factory()->farmer()->create();
    Helper::makeAddress($farmer);

    $this->actingAs($farmer)->postJson("/api/v1/demands/{$demand->id}/offers", [
        'quantity_kg' => 1000000,
        'price_per_kg' => 99999999,
    ])->assertStatus(422)->assertJsonValidationErrors('quantity_kg');
});

it('filters the farmer offer list by status', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $otherFarmer = User::factory()->farmer()->create();
    Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::PENDING]);
    Helper::makeOffer($demand, $otherFarmer, ['status' => DemandOfferStatus::ACCEPTED]);

    $this->actingAs($farmer)->getJson('/api/v1/farmer/offers?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', DemandOfferStatus::PENDING->value);

    $this->actingAs($farmer)->getJson('/api/v1/farmer/offers?status=accepted')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    // Unknown values are ignored, so all of the farmer's offers return.
    $this->actingAs($farmer)->getJson('/api/v1/farmer/offers?status=bogus')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
