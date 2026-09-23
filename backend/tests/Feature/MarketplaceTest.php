<?php

use App\Constants\PaymentConstants;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\CashPaymentApproved;
use App\Domain\Marketplace\Events\CashPaymentRequested;
use App\Domain\Marketplace\Events\ContractPurchased;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows a farmer to publish an accepted recommendation as a contract', function () {
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

    $response = $this->actingAs($farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", [
        'title' => '500kg Jasmine Rice',
        'description' => 'Fresh harvest',
        'quantity_kg' => 500,
        'price_per_kg' => 50,
        'estimated_harvest_date' => now()->addDays(30)->format('Y-m-d'),
        'expiry_date' => now()->addDays(15)->format('Y-m-d'),
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.title', '500kg Jasmine Rice');
    $response->assertJsonPath('data.status', 'available');

    $this->assertDatabaseHas('forward_contracts', [
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'status' => ContractStatus::AVAILABLE->value,
    ]);
});

it('prevents publishing a recommendation that is rejected', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::REJECTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);

    $response = $this->actingAs($farmer)->postJson("/api/v1/recommendations/{$recommendation->id}/publish", [
        'title' => 'Test',
        'quantity_kg' => 500,
        'price_per_kg' => 50,
        'estimated_harvest_date' => now()->addDays(30)->format('Y-m-d'),
        'expiry_date' => now()->addDays(15)->format('Y-m-d'),
    ]);

    $response->assertStatus(422); // ValidationException is thrown
});

it('allows anyone to browse the marketplace', function () {
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

    ForwardContract::factory()->count(3)->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    $response = $this->getJson('/api/v1/market/contracts');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

it('allows a buyer to create a checkout session', function () {
    $buyer = User::factory()->buyer()->create();

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

    $contract = ForwardContract::factory()->available()->create([
        'total_price' => 5000,
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    $mockService = Mockery::mock(PayMongoService::class);
    $mockService->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn([
            'checkout_url' => 'https://paymongo.com/checkout/test',
            'checkout_id' => 'cs_test_123',
        ]);

    $this->app->instance(PayMongoService::class, $mockService);

    $response = $this->actingAs($buyer)->postJson("/api/v1/market/contracts/{$contract->id}/checkout", [
        'quantity_kg' => $contract->quantity_kg,
        'payment_option' => 'paymongo',
    ]);

    $response->assertOk();
    $response->assertJsonPath('checkout_url', 'https://paymongo.com/checkout/test');

    $this->assertDatabaseHas('purchases', [
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
        'payment_status' => PaymentStatus::PENDING->value,
    ]);

    $this->assertDatabaseHas('forward_contracts', [
        'id' => $contract->id,
        'status' => ContractStatus::RESERVED->value,
    ]);
});

it('processes a paymongo webhook successfully', function () {
    Event::fake([ContractPurchased::class]);

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

    $contract = ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    $purchase = Purchase::factory()->create([
        'forward_contract_id' => $contract->id,
        'paymongo_checkout_id' => 'cs_test_webhook123',
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $mockService = Mockery::mock(PayMongoService::class);
    $mockService->shouldReceive('verifyWebhookSignature')->andReturn(true);
    $mockService->shouldReceive('parseWebhookEvent')->andReturn([
        'data' => [
            'attributes' => [
                'type' => PaymentConstants::EVENT_PAYMENT_PAID,
                'data' => [
                    'id' => 'cs_test_webhook123',
                    'attributes' => [
                        'payment_intent' => ['id' => 'pi_test_intent123'],
                        'payment_method_used' => 'gcash',
                    ],
                ],
            ],
        ],
    ]);

    $this->app->instance(PayMongoService::class, $mockService);

    $response = $this->postJson('/api/v1/webhooks/paymongo', [], [
        'Paymongo-Signature' => 'test-signature',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('purchases', [
        'id' => $purchase->id,
        'payment_status' => PaymentStatus::COMPLETED->value,
        'payment_method' => PaymentMethod::GCASH->value,
    ]);

    $this->assertDatabaseHas('forward_contracts', [
        'id' => $contract->id,
        'status' => ContractStatus::SOLD->value,
    ]);

    Event::assertDispatched(ContractPurchased::class);
});

it('allows a farmer to list their own contracts', function () {
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

    ForwardContract::factory()->count(3)->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    $response = $this->actingAs($farmer)->getJson('/api/v1/farmer/contracts');

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'title', 'total_price', 'status'],
        ],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

it('allows a farmer to fetch their contract stats', function () {
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

    ForwardContract::factory()->count(2)->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    ForwardContract::factory()->count(1)->sold()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'total_price' => 1000,
    ]);

    $response = $this->actingAs($farmer)->getJson('/api/v1/farmer/contracts/stats');

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'total_listed' => 3,
            'total_sold' => 1,
            'total_revenue' => 1000,
        ],
    ]);
});

it('allows a buyer to list their own purchases', function () {
    $buyer = User::factory()->buyer()->create();

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

    $contract = ForwardContract::factory()->sold()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);

    Purchase::factory()->count(2)->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/buyer/purchases');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'amount_paid', 'payment_status', 'contract'],
        ],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

it('allows a farmer to create a manual harvest listing', function () {
    $farmer = User::factory()->farmer()->create();

    $response = $this->actingAs($farmer)->postJson('/api/v1/farmer/listings', [
        'title' => 'Manual Rice',
        'crop_name' => 'Rice',
        'quantity_kg' => 1000,
        'price_per_kg' => 45.5,
        'estimated_harvest_date' => now()->addDays(20)->format('Y-m-d'),
        'shelf_life_days' => 180,
        'is_harvest_available' => false,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('listing.title', 'Manual Rice');

    $this->assertDatabaseHas('harvest_listings', [
        'farmer_id' => $farmer->id,
        'crop_name' => 'Rice',
        'quantity_kg' => 1000,
        'price_per_kg' => 45.50,
    ]);
});

it('allows a buyer to create a cash checkout session', function () {
    $buyer = User::factory()->buyer()->create();
    $farmer = User::factory()->farmer()->create();

    $listing = HarvestListing::create([
        'title' => 'Test Listing',
        'farmer_id' => $farmer->id,
        'crop_name' => 'Rice',
        'quantity_kg' => 1000,
        'price_per_kg' => 50,
        'total_price' => 50000,
        'estimated_harvest_date' => now()->addDays(20),
        'expiry_date' => now()->addDays(30),
        'shelf_life_days' => 180,
        'is_harvest_available' => false,
    ]);

    Event::fake([CashPaymentRequested::class]);

    $response = $this->actingAs($buyer)->postJson("/api/v1/market/listings/{$listing->id}/checkout", [
        'quantity_kg' => 100,
        'payment_option' => 'cash',
    ]);

    $response->assertOk();
    $response->assertJsonPath('message', 'Cash purchase request created. Please wait for farmer approval.');

    $this->assertDatabaseHas('purchases', [
        'buyer_id' => $buyer->id,
        'payment_status' => PaymentStatus::PENDING->value,
        'cash_payment_status' => CashPaymentStatus::PENDING_APPROVAL->value,
    ]);

    Event::assertDispatched(CashPaymentRequested::class);
});

it('allows a farmer to approve a partial cash payment', function () {
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();

    $listing = HarvestListing::create([
        'title' => 'Test Listing',
        'farmer_id' => $farmer->id,
        'crop_name' => 'Rice',
        'quantity_kg' => 1000,
        'price_per_kg' => 50,
        'total_price' => 50000,
        'estimated_harvest_date' => now()->addDays(20),
        'expiry_date' => now()->addDays(30),
        'shelf_life_days' => 180,
        'is_harvest_available' => false,
    ]);

    $purchase = Purchase::factory()->create([
        'harvest_listing_id' => $listing->id,
        'forward_contract_id' => null,
        'buyer_id' => $buyer->id,
        'payment_status' => PaymentStatus::PENDING,
        'cash_payment_status' => CashPaymentStatus::PENDING_APPROVAL,
        'total_contract_amount' => 5000,
        'is_downpayment' => true,
    ]);

    Event::fake([CashPaymentApproved::class]);

    $response = $this->actingAs($farmer)->postJson("/api/v1/farmer/purchases/{$purchase->id}/approve", [
        'type' => 'partial',
        'amount' => 500,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('purchases', [
        'id' => $purchase->id,
        'cash_payment_status' => CashPaymentStatus::PARTIALLY_PAID->value,
        'cash_amount_confirmed' => 500,
    ]);

    Event::assertDispatched(CashPaymentApproved::class);
});
