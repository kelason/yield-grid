<?php

// OWASP A03:2021 — Injection.

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createInjectionRecommendation(): CropRecommendation
{
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);

    return CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
}

function createInjectionBuyerWithPurchase(CropRecommendation $recommendation): User
{
    $buyer = User::factory()->buyer()->create();
    Purchase::factory()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => ForwardContract::factory()->create([
            'crop_recommendation_id' => $recommendation->id,
        ])->id,
    ]);

    return $buyer;
}

it('treats SQL injection payloads in purchase search as plain text', function () {
    $recommendation = createInjectionRecommendation();
    $buyer = createInjectionBuyerWithPurchase($recommendation);
    createInjectionBuyerWithPurchase($recommendation);

    foreach (["' OR '1'='1", "%' OR 1=1 --", "'; DROP TABLE purchases; --"] as $payload) {
        $response = $this->actingAs($buyer)
            ->getJson('/api/v1/buyer/purchases?search='.urlencode($payload));

        $response->assertOk();
        // A successful injection would return rows; a bound parameter matches nothing.
        $response->assertJsonCount(0, 'data');
    }

    // Table is intact and normal search still works afterwards.
    $this->actingAs($buyer)
        ->getJson('/api/v1/buyer/purchases?search=Rice')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('ignores malicious sort parameters on purchase listing', function () {
    $buyer = createInjectionBuyerWithPurchase(createInjectionRecommendation());

    $this->actingAs($buyer)
        ->getJson('/api/v1/buyer/purchases?sort=amount_paid;DROP TABLE purchases')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('ignores mass-assigned ownership fields on farm creation', function () {
    $farmer = User::factory()->farmer()->create();
    $victim = User::factory()->farmer()->create();

    $this->actingAs($farmer)
        ->postJson('/api/v1/farms', [
            'name' => 'Tampered Farm',
            'user_id' => $victim->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('farms', [
        'name' => 'Tampered Farm',
        'user_id' => $farmer->id,
    ]);
    $this->assertDatabaseMissing('farms', [
        'name' => 'Tampered Farm',
        'user_id' => $victim->id,
    ]);
});

it('rejects role tampering on registration', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);

    $this->assertDatabaseMissing('users', ['email' => 'mallory@example.com']);
});

it('rejects non-numeric quantities at the boundary', function () {
    $farmer = User::factory()->farmer()->create();

    $listingId = $this->actingAs($farmer)->postJson('/api/v1/farmer/listings', [
        'title' => '50kg Corn',
        'crop_name' => 'Corn',
        'quantity_kg' => 50,
        'price_per_kg' => 30,
        'shelf_life_days' => 7,
        'is_harvest_available' => true,
    ])->assertCreated()->json('listing.id');

    $buyer = User::factory()->buyer()->create();

    $this->actingAs($buyer)
        ->postJson("/api/v1/market/listing/{$listingId}/checkout", [
            'payment_option' => 'cash',
            'quantity_kg' => 'ten',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity_kg']);
});
