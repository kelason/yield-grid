<?php

// OWASP A08:2021 — Software and Data Integrity Failures.

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createWebhookPurchase(string $checkoutId): Purchase
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

    return Purchase::factory()->create([
        'buyer_id' => User::factory()->buyer()->create()->id,
        'forward_contract_id' => $contract->id,
        'paymongo_checkout_id' => $checkoutId,
    ]);
}

function securityPaymongoPayload(string $checkoutId, string $eventType = 'checkout_session.payment.paid'): string
{
    return json_encode([
        'data' => [
            'attributes' => [
                'type' => $eventType,
                'data' => [
                    'id' => $checkoutId,
                    'attributes' => [
                        'payment_intent' => ['id' => 'pi_test_123'],
                        'payment_method_used' => 'gcash',
                    ],
                ],
            ],
        ],
    ]);
}

function securityPaymongoSignature(string $payload, string $secret, ?int $timestamp = null): string
{
    $timestamp ??= time();

    return 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
}

function postSignedWebhook(string $payload, string $signature): TestResponse
{
    return test()->call(
        'POST',
        '/api/v1/webhooks/paymongo',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => $signature],
        $payload
    );
}

beforeEach(function () {
    config(['services.paymongo.webhook_secret' => 'test-secret']);
});

it('rejects webhooks without a signature', function () {
    $purchase = createWebhookPurchase('cs_test_unsigned');

    $this->call('POST', '/api/v1/webhooks/paymongo', [], [], [], ['CONTENT_TYPE' => 'application/json'], securityPaymongoPayload('cs_test_unsigned'))
        ->assertStatus(400);

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('rejects webhooks with a tampered payload', function () {
    $purchase = createWebhookPurchase('cs_test_tampered');
    $payload = securityPaymongoPayload('cs_test_tampered');
    $signature = securityPaymongoSignature($payload, 'test-secret');

    postSignedWebhook($payload.' ', $signature)->assertStatus(400);

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('rejects webhooks with a stale timestamp', function () {
    $purchase = createWebhookPurchase('cs_test_stale');
    $payload = securityPaymongoPayload('cs_test_stale');
    $signature = securityPaymongoSignature($payload, 'test-secret', time() - 600);

    postSignedWebhook($payload, $signature)->assertStatus(400);

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('fails closed when no webhook secret is configured', function () {
    config(['services.paymongo.webhook_secret' => '']);

    $purchase = createWebhookPurchase('cs_test_nosecret');
    $payload = securityPaymongoPayload('cs_test_nosecret');
    $signature = securityPaymongoSignature($payload, 'test-secret');

    postSignedWebhook($payload, $signature)->assertStatus(400);

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('processes a valid payment webhook exactly once (idempotent)', function () {
    $purchase = createWebhookPurchase('cs_test_valid');
    $payload = securityPaymongoPayload('cs_test_valid');
    $signature = securityPaymongoSignature($payload, 'test-secret');

    postSignedWebhook($payload, $signature)->assertOk();
    postSignedWebhook($payload, $signature)->assertOk()->assertSee('Already processed');

    $purchase = $purchase->fresh();

    expect($purchase->payment_status)->toBe(PaymentStatus::COMPLETED)
        ->and($purchase->paymongo_payment_id)->toBe('pi_test_123')
        ->and(ForwardContract::find($purchase->forward_contract_id)->status)->toBe(ContractStatus::SOLD);
});

it('acknowledges unknown webhook event types without side effects', function () {
    $purchase = createWebhookPurchase('cs_test_unknown');
    $payload = securityPaymongoPayload('cs_test_unknown', 'checkout_session.something.new');
    $signature = securityPaymongoSignature($payload, 'test-secret');

    postSignedWebhook($payload, $signature)->assertOk();

    expect($purchase->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});
