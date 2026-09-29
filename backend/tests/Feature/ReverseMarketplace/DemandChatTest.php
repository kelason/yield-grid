<?php

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
});

it('blocks chat before the buyer accepts an offer', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeOffer($demand, $farmer);

    $this->actingAs($buyer)->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $farmer->id,
    ])->assertForbidden();

    $this->actingAs($farmer)->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $buyer->id,
    ])->assertForbidden();
});

it('unlocks chat once the buyer accepts the offer', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);

    $this->actingAs($buyer)->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $farmer->id,
    ])->assertCreated();

    $this->actingAs($farmer)->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $buyer->id,
    ])->assertCreated();
});

it('keeps chat open through payment and completion', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::COMPLETED]);
    $stranger = User::factory()->farmer()->create();

    $this->actingAs($buyer)->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $farmer->id,
    ])->assertCreated();

    // Unrelated farmers still cannot message the buyer.
    $this->actingAs($stranger)->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $buyer->id,
    ])->assertForbidden();

    expect($offer->fresh()->status)->toBe(DemandOfferStatus::COMPLETED);
});
