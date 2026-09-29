<?php

use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\Purchase;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('completes the farm-to-market cash journey', function () {
    // 1. Farmer registers and verifies their email.
    $farmerEmail = 'farmer@example.com';
    $farmerToken = $this->postJson('/api/v1/register', [
        'name' => 'Maria Farmer',
        'email' => $farmerEmail,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ])->assertCreated()->json('token');

    User::where('email', $farmerEmail)->firstOrFail()->markEmailAsVerified();

    // 2. Farmer creates a farm.
    $farmId = $this->withToken($farmerToken)->postJson('/api/v1/farms', [
        'name' => 'Green Acres',
    ])->assertCreated()->assertJsonPath('data.name', 'Green Acres')->json('data.id');

    // 3. Farmer draws a plot on the farm.
    $this->withToken($farmerToken)->postJson("/api/v1/farms/{$farmId}/plots", [
        'name' => 'North Field',
        'soil_type' => 'loamy',
        'coordinates' => [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]],
    ])->assertCreated()->assertJsonPath('data.name', 'North Field');

    // 4. Farmer lists part of the harvest for sale.
    $listingId = $this->withToken($farmerToken)->postJson('/api/v1/farmer/listings', [
        'title' => '100kg Jasmine Rice',
        'crop_name' => 'Jasmine Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 50,
        'shelf_life_days' => 7,
        'is_harvest_available' => true,
    ])->assertCreated()->json('listing.id');

    // 5. The listing is publicly visible on the marketplace.
    $this->getJson('/api/v1/market/contracts')
        ->assertOk()
        ->assertJsonFragment(['title' => '100kg Jasmine Rice']);

    // 6. Buyer registers, verifies, and requests a cash purchase.
    $buyerEmail = 'buyer@example.com';
    $buyerToken = $this->postJson('/api/v1/register', [
        'name' => 'Jose Buyer',
        'email' => $buyerEmail,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'buyer',
    ])->assertCreated()->json('token');

    User::where('email', $buyerEmail)->firstOrFail()->markEmailAsVerified();

    $purchaseId = $this->withToken($buyerToken)->postJson("/api/v1/market/listing/{$listingId}/checkout", [
        'payment_option' => 'cash',
        'quantity_kg' => 10,
    ])->assertOk()->json('purchase_id');

    // 7. Farmer sees the pending purchase and approves full cash payment.
    $this->withToken($farmerToken)->getJson('/api/v1/farmer/purchases')
        ->assertOk()
        ->assertJsonFragment(['id' => $purchaseId]);

    $this->withToken($farmerToken)->postJson("/api/v1/farmer/purchases/{$purchaseId}/approve", [
        'type' => 'full',
    ])->assertOk()
        ->assertJsonPath('data.payment_status', PaymentStatus::COMPLETED->value)
        ->assertJsonPath('data.cash_payment_status', CashPaymentStatus::FULLY_PAID->value);

    // 8. Buyer sees the completed purchase.
    $this->withToken($buyerToken)->getJson("/api/v1/buyer/purchases/{$purchaseId}")
        ->assertOk()
        ->assertJsonPath('data.payment_status', PaymentStatus::COMPLETED->value);

    // 9. Final state: purchase completed and the split listing sold.
    $purchase = Purchase::findOrFail($purchaseId);

    expect($purchase->payment_status)->toBe(PaymentStatus::COMPLETED)
        ->and($purchase->harvestListing->status)->toBe(ContractStatus::SOLD);
});

it('blocks unverified farmers from selling', function () {
    $token = $this->postJson('/api/v1/register', [
        'name' => 'Unverified Farmer',
        'email' => 'unverified@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ])->assertCreated()->json('token');

    $this->withToken($token)->postJson('/api/v1/farms', [
        'name' => 'Should Not Exist',
    ])->assertForbidden();

    $this->assertDatabaseMissing('farms', ['name' => 'Should Not Exist']);
});

it('blocks buyers from selling and farmers from buying', function () {
    $buyer = User::factory()->buyer()->create();
    $farmer = User::factory()->farmer()->create();

    $this->actingAs($buyer)->postJson('/api/v1/farms', [
        'name' => 'Buyer Farm',
    ])->assertForbidden();

    $this->actingAs($farmer)->postJson('/api/v1/market/listing/1/checkout', [
        'payment_option' => 'cash',
        'quantity_kg' => 10,
    ])->assertForbidden();
});
