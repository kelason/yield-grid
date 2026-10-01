<?php

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
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
    $this->approveCash = function (int $purchaseId, array $payload) {
        return $this->actingAs($this->farmer)->postJson("/api/v1/farmer/purchases/{$purchaseId}/approve", $payload);
    };
});

it('accepts a partial approval at the min amount', function () {
    $offer = Helper::makeOffer($this->demand, $this->farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $checkout = $this->actingAs($this->buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();

    ($this->approveCash)($checkout->json('purchase_id'), [
        'type' => 'partial',
        'amount' => MarketplaceConstants::CASH_APPROVAL_AMOUNT_MIN,
    ])->assertOk();
});

it('rejects a partial approval below the min amount', function () {
    $offer = Helper::makeOffer($this->demand, $this->farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $checkout = $this->actingAs($this->buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();

    ($this->approveCash)($checkout->json('purchase_id'), [
        'type' => 'partial',
        'amount' => 0,
    ])->assertStatus(422)->assertJsonValidationErrors(['amount']);
});

it('accepts a partial approval at the max amount', function () {
    $bigDemand = Helper::makeDemand($this->buyer, null, [
        'quantity_kg' => 1000000,
        'remaining_quantity_kg' => 1000000,
        'target_price_per_kg' => 100,
        'total_budget' => 100000000,
    ]);
    // Total 1,000,000 x 100 = 100,000,000 leaves headroom for the max amount.
    $offer = Helper::makeOffer($bigDemand, $this->farmer, [
        'status' => DemandOfferStatus::ACCEPTED,
        'quantity_kg' => 1000000,
        'price_per_kg' => 100,
        'total_price' => 100000000,
    ]);
    $checkout = $this->actingAs($this->buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();

    ($this->approveCash)($checkout->json('purchase_id'), [
        'type' => 'partial',
        'amount' => MarketplaceConstants::CASH_APPROVAL_AMOUNT_MAX,
    ])->assertOk();
});

it('rejects a partial approval above the max amount', function () {
    $offer = Helper::makeOffer($this->demand, $this->farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $checkout = $this->actingAs($this->buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();

    ($this->approveCash)($checkout->json('purchase_id'), [
        'type' => 'partial',
        'amount' => MarketplaceConstants::CASH_APPROVAL_AMOUNT_MAX + 1,
    ])->assertStatus(422)->assertJsonValidationErrors(['amount']);
});

it('rejects a partial approval above the outstanding balance with conflict', function () {
    $offer = Helper::makeOffer($this->demand, $this->farmer, [
        'status' => DemandOfferStatus::ACCEPTED,
        'quantity_kg' => 100,
        'price_per_kg' => 400,
        'total_price' => 40000,
    ]);
    $checkout = $this->actingAs($this->buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();

    ($this->approveCash)($checkout->json('purchase_id'), [
        'type' => 'partial',
        'amount' => 50000,
    ])->assertStatus(409);
});

it('rejects approving an already fully paid purchase with conflict', function () {
    $offer = Helper::makeOffer($this->demand, $this->farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $checkout = $this->actingAs($this->buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();
    $purchaseId = $checkout->json('purchase_id');

    ($this->approveCash)($purchaseId, ['type' => 'full'])->assertOk();

    ($this->approveCash)($purchaseId, ['type' => 'full'])->assertStatus(409);
});
