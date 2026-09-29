<?php

use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\DemandOfferPaid;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Services\PaymentGatewayInterface;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper as Helper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Helper::fakePsgc();
    config(['services.paymongo.webhook_secret' => 'test-secret']);
});

function reversePaymongoPayload(string $checkoutId, string $eventType = 'checkout_session.payment.paid'): string
{
    return json_encode([
        'data' => [
            'attributes' => [
                'type' => $eventType,
                'data' => [
                    'id' => $checkoutId,
                    'attributes' => [
                        'payment_intent' => ['id' => 'pi_test_offer'],
                        'payment_method_used' => 'gcash',
                    ],
                ],
            ],
        ],
    ]);
}

function reversePaymongoSignature(string $payload): string
{
    $timestamp = time();

    return 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$payload, 'test-secret');
}

function reversePostSignedWebhook(string $payload): TestResponse
{
    return test()->call(
        'POST',
        '/api/v1/webhooks/paymongo',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => reversePaymongoSignature($payload)],
        $payload
    );
}

it('checks out an accepted offer with a downpayment when needed-by is in the future', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
        'total_price' => 6600,
        'status' => DemandOfferStatus::ACCEPTED,
    ]);

    $mock = Mockery::mock(PayMongoService::class);
    $mock->shouldReceive('createCheckoutSession')->once()->andReturn([
        'checkout_url' => 'https://paymongo.com/checkout/offer',
        'checkout_id' => 'cs_offer_123',
    ]);
    $this->app->instance(PayMongoService::class, $mock);

    $response = $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'paymongo',
    ]);

    $response->assertOk();
    $response->assertJsonPath('checkout_url', 'https://paymongo.com/checkout/offer');

    $this->assertDatabaseHas('purchases', [
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'payment_status' => PaymentStatus::PENDING->value,
        'is_downpayment' => true,
        'amount_paid' => 660.0,
        'total_contract_amount' => 6600.0,
    ]);
});

it('refuses checkout for non-accepted offers and duplicate payments', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $pending = Helper::makeOffer($demand, $farmer);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$pending->id}/checkout", [
        'payment_option' => 'paymongo',
    ])->assertStatus(409);

    $accepted = Helper::makeOffer($demand, User::factory()->farmer()->create(), [
        'status' => DemandOfferStatus::ACCEPTED,
    ]);
    Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $accepted->id,
        'quantity_kg' => 150,
        'amount_paid' => 6600,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'is_downpayment' => false,
        'total_contract_amount' => 6600,
    ]);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$accepted->id}/checkout", [
        'payment_option' => 'paymongo',
    ])->assertStatus(409);
});

it('completes an offer payment through the webhook exactly once', function () {
    Event::fake([DemandOfferPaid::class]);

    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 6600,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_paid',
    ]);

    reversePostSignedWebhook(reversePaymongoPayload('cs_offer_paid'))->assertOk();

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::COMPLETED);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PAID);
    Event::assertDispatched(DemandOfferPaid::class, 1);

    // Replay is idempotent.
    reversePostSignedWebhook(reversePaymongoPayload('cs_offer_paid'))->assertOk();
    Event::assertDispatched(DemandOfferPaid::class, 1);
});

it('marks the offer partially paid when the webhook confirms a downpayment', function () {
    Event::fake([DemandOfferPaid::class]);

    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_down',
        'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);

    reversePostSignedWebhook(reversePaymongoPayload('cs_offer_down'))->assertOk();

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::COMPLETED);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PARTIALLY_PAID);
    Event::assertDispatched(DemandOfferPaid::class, 1);
});

it('keeps the offer accepted when payment fails or expires', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();

    $failedOffer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $failed = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $failedOffer->id,
        'quantity_kg' => 150,
        'amount_paid' => 6600,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_failed',
    ]);
    reversePostSignedWebhook(reversePaymongoPayload('cs_offer_failed', 'checkout_session.payment.failed'))->assertOk();
    expect($failed->fresh()->payment_status)->toBe(PaymentStatus::FAILED);
    expect($failedOffer->fresh()->status)->toBe(DemandOfferStatus::ACCEPTED);

    $expiredOffer = Helper::makeOffer($demand, User::factory()->farmer()->create(), ['status' => DemandOfferStatus::ACCEPTED]);
    $expired = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $expiredOffer->id,
        'quantity_kg' => 100,
        'amount_paid' => 4400,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_expired',
    ]);
    reversePostSignedWebhook(reversePaymongoPayload('cs_offer_expired', 'checkout_session.expired'))->assertOk();
    expect($expired->fresh()->payment_status)->toBe(PaymentStatus::EXPIRED);
    expect($expiredOffer->fresh()->status)->toBe(DemandOfferStatus::ACCEPTED);
});

it('verifies a pending offer purchase through the verify endpoint', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 6600,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_verify',
    ]);

    $mock = Mockery::mock(PaymentGatewayInterface::class);
    $mock->shouldReceive('getCheckoutSession')->once()->with('cs_offer_verify')->andReturn([
        'data' => ['attributes' => [
            'payments' => [['id' => 'pay_1', 'attributes' => ['status' => 'paid', 'source' => ['type' => 'gcash']]]],
        ]],
    ]);
    $this->app->instance(PaymentGatewayInterface::class, $mock);

    $this->actingAs($buyer)->getJson("/api/v1/checkout/{$purchase->id}/verify")
        ->assertOk()
        ->assertJsonPath('status', PaymentStatus::COMPLETED->value);

    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PAID);
});

it('marks the offer partially paid when verifying a downpayment purchase', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_verify_down',
        'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);

    $mock = Mockery::mock(PaymentGatewayInterface::class);
    $mock->shouldReceive('getCheckoutSession')->once()->with('cs_offer_verify_down')->andReturn([
        'data' => ['attributes' => [
            'payments' => [['id' => 'pay_1', 'attributes' => ['status' => 'paid', 'source' => ['type' => 'gcash']]]],
        ]],
    ]);
    $this->app->instance(PaymentGatewayInterface::class, $mock);

    $this->actingAs($buyer)->getJson("/api/v1/checkout/{$purchase->id}/verify")
        ->assertOk()
        ->assertJsonPath('status', PaymentStatus::COMPLETED->value);

    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PARTIALLY_PAID);
});

it('supports cash payment with farmer approval for offers', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);

    $checkout = $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();
    $purchaseId = $checkout->json('purchase_id');

    $approve = $this->actingAs($farmer)->postJson("/api/v1/farmer/purchases/{$purchaseId}/approve", [
        'type' => 'full',
    ]);
    $approve->assertOk();

    $purchase = Purchase::findOrFail($purchaseId);
    expect($purchase->payment_status)->toBe(PaymentStatus::COMPLETED);
    expect((float) $purchase->amount_paid)->toBe(6600.0);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PAID);
});

it('tracks confirmed cash through partial then full approval', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, [
        'quantity_kg' => 150,
        'price_per_kg' => 44,
        'total_price' => 6600,
        'status' => DemandOfferStatus::ACCEPTED,
    ]);

    $checkout = $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/checkout", [
        'payment_option' => 'cash',
    ]);
    $checkout->assertOk();
    $purchaseId = $checkout->json('purchase_id');

    $this->actingAs($farmer)->postJson("/api/v1/farmer/purchases/{$purchaseId}/approve", [
        'type' => 'partial',
        'amount' => 660,
    ])->assertOk();

    $partial = Purchase::findOrFail($purchaseId);
    expect((float) $partial->amount_paid)->toBe(660.0);
    expect($partial->payment_status)->toBe(PaymentStatus::PENDING);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PARTIALLY_PAID);

    $this->actingAs($farmer)->postJson("/api/v1/farmer/purchases/{$purchaseId}/approve", [
        'type' => 'full',
        'amount' => 6600,
    ])->assertOk();

    $full = Purchase::findOrFail($purchaseId);
    expect($full->payment_status)->toBe(PaymentStatus::COMPLETED);
    expect((float) $full->amount_paid)->toBe(6600.0);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::PAID);
});

it('walks an offer from paid to delivered to completed and fulfills the demand', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, [
        'remaining_quantity_kg' => 0,
        'status' => DemandStatus::FULLY_ALLOCATED,
    ]);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::PAID]);

    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$offer->id}/mark-delivered")->assertOk();
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::DELIVERED);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/confirm-completed")->assertOk();
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::COMPLETED);
    expect($demand->fresh()->status)->toBe(DemandStatus::FULFILLED);
});

it('walks a partially paid offer from delivered to completed and fulfills the demand', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer, null, [
        'remaining_quantity_kg' => 0,
        'status' => DemandStatus::FULLY_ALLOCATED,
    ]);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::PARTIALLY_PAID]);

    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$offer->id}/mark-delivered")->assertOk();
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::DELIVERED);

    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/confirm-completed")->assertOk();
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::COMPLETED);
    expect($demand->fresh()->status)->toBe(DemandStatus::FULFILLED);
});

it('enforces delivery transition order and ownership', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);

    // Cannot mark delivered before payment.
    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$offer->id}/mark-delivered")
        ->assertStatus(409);

    // Cannot confirm before delivery.
    $offer->update(['status' => DemandOfferStatus::PAID]);
    $this->actingAs($buyer)->postJson("/api/v1/buyer/offers/{$offer->id}/confirm-completed")
        ->assertStatus(409);

    // Strangers cannot transition.
    $stranger = User::factory()->farmer()->create();
    $this->actingAs($stranger)->postJson("/api/v1/farmer/offers/{$offer->id}/mark-delivered")
        ->assertForbidden();
});

it('cancels a pending offer checkout by purchase id and keeps the offer payable', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::PENDING,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_cancel',
    ]);

    $this->actingAs($buyer)->postJson("/api/v1/checkout/{$purchase->id}/cancel")
        ->assertOk()
        ->assertJsonPath('message', 'Checkout cancelled successfully.');

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::FAILED);
    expect($offer->fresh()->status)->toBe(DemandOfferStatus::ACCEPTED);
});

it('returns 404 when cancelling an unknown or already-processed checkout', function () {
    $buyer = User::factory()->buyer()->create();

    $this->actingAs($buyer)->postJson('/api/v1/checkout/999999/cancel')
        ->assertNotFound();

    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::PAID]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 6600,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::COMPLETED,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_done',
    ]);

    $this->actingAs($buyer)->postJson("/api/v1/checkout/{$purchase->id}/cancel")
        ->assertNotFound();
});

it('lets the farmer confirm the balance on a delivered downpayment offer', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::DELIVERED]);
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::COMPLETED,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_settle',
        'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);

    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$offer->id}/settle-balance")
        ->assertOk();

    expect((float) $purchase->fresh()->amount_paid)->toBe(6600.0);

    // Settling twice is rejected.
    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$offer->id}/settle-balance")
        ->assertStatus(409);
});

it('refuses balance settlement before delivery, without a downpayment, or by strangers', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();

    // Not delivered yet.
    $pending = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::PARTIALLY_PAID]);
    Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $pending->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::COMPLETED,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_settle_early',
        'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);
    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$pending->id}/settle-balance")
        ->assertStatus(409);

    // Delivered but fully paid online (no downpayment).
    $secondFarmer = User::factory()->farmer()->create();
    $full = Helper::makeOffer($demand, $secondFarmer, ['status' => DemandOfferStatus::DELIVERED]);
    Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $full->id,
        'quantity_kg' => 150,
        'amount_paid' => 6600,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::COMPLETED,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_settle_full',
        'is_downpayment' => false,
        'total_contract_amount' => 6600,
    ]);
    $this->actingAs($secondFarmer)->postJson("/api/v1/farmer/offers/{$full->id}/settle-balance")
        ->assertStatus(409);

    // Strangers cannot settle someone else's offer.
    $stranger = User::factory()->farmer()->create();
    $this->actingAs($stranger)->postJson("/api/v1/farmer/offers/{$full->id}/settle-balance")
        ->assertForbidden();
});

it('settles against the completed purchase when an earlier checkout was cancelled', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);
    $farmer = User::factory()->farmer()->create();
    $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::COMPLETED]);

    // Older cancelled checkout attempt.
    Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::FAILED,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_settle_cancelled',
        'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);

    // Newer completed downpayment.
    $purchase = Purchase::create([
        'buyer_id' => $buyer->id,
        'crop_demand_offer_id' => $offer->id,
        'quantity_kg' => 150,
        'amount_paid' => 660,
        'currency' => 'PHP',
        'payment_status' => PaymentStatus::COMPLETED,
        'payment_method' => PaymentMethod::GCASH,
        'paymongo_checkout_id' => 'cs_offer_settle_retry',
        'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);

    $this->actingAs($farmer)->postJson("/api/v1/farmer/offers/{$offer->id}/settle-balance")
        ->assertOk();

    expect((float) $purchase->fresh()->amount_paid)->toBe(6600.0);
});

it('filters the buyer purchase list by display status', function () {
    $buyer = User::factory()->buyer()->create();
    $demand = Helper::makeDemand($buyer);

    $makePurchase = function (array $overrides) use ($buyer, $demand) {
        $farmer = User::factory()->farmer()->create();
        $offer = Helper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);

        return Purchase::create(array_merge([
            'buyer_id' => $buyer->id,
            'crop_demand_offer_id' => $offer->id,
            'quantity_kg' => 150,
            'currency' => 'PHP',
            'payment_method' => PaymentMethod::GCASH,
        ], $overrides));
    };

    $pending = $makePurchase([
        'amount_paid' => 660, 'payment_status' => PaymentStatus::PENDING,
        'paymongo_checkout_id' => 'cs_filter_pending',
    ]);
    $partial = $makePurchase([
        'amount_paid' => 660, 'payment_status' => PaymentStatus::COMPLETED,
        'paymongo_checkout_id' => 'cs_filter_partial', 'is_downpayment' => true,
        'total_contract_amount' => 6600,
    ]);
    $paid = $makePurchase([
        'amount_paid' => 6600, 'payment_status' => PaymentStatus::COMPLETED,
        'paymongo_checkout_id' => 'cs_filter_paid', 'is_downpayment' => false,
        'total_contract_amount' => 6600,
    ]);
    $failed = $makePurchase([
        'amount_paid' => 660, 'payment_status' => PaymentStatus::FAILED,
        'paymongo_checkout_id' => 'cs_filter_failed',
    ]);
    $cashPartial = $makePurchase([
        'amount_paid' => 660, 'payment_status' => PaymentStatus::PENDING,
        'paymongo_checkout_id' => 'cs_filter_cash', 'cash_payment_status' => CashPaymentStatus::PARTIALLY_PAID,
        'cash_amount_confirmed' => 660, 'total_contract_amount' => 6600,
    ]);

    $idsFor = fn (string $status) => $this->actingAs($buyer)
        ->getJson("/api/v1/buyer/purchases?status={$status}&per_page=50")
        ->assertOk()
        ->json('data.*.id');

    expect($idsFor('pending'))->toBe([$pending->id]);
    expect($idsFor('partially_paid'))->toEqualCanonicalizing([$partial->id, $cashPartial->id]);
    expect($idsFor('paid'))->toBe([$paid->id]);
    expect($idsFor('failed'))->toBe([$failed->id]);
    expect($idsFor('bogus'))->toHaveCount(5);
});
