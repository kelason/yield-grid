<?php

// OWASP A07:2021 — Brute-force and spam protection via rate limiting.

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

function createRateLimitBuyerWithPurchase(): array
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
    $buyer = User::factory()->buyer()->create();
    $purchase = Purchase::factory()->completed()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
    ]);

    return [$buyer, $purchase];
}

it('locks out login after 5 rapid attempts, even with correct credentials', function () {
    $user = User::factory()->create();

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    // The 6th attempt is blocked before credentials are even checked.
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertTooManyRequests();
});

it('throttles mass account registration', function () {
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->postJson('/api/v1/register', [
            'name' => 'Spammer',
            'email' => "spammer{$attempt}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'buyer',
        ])->assertCreated();
    }

    $this->postJson('/api/v1/register', [
        'name' => 'Spammer',
        'email' => 'spammer11@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'buyer',
    ])->assertTooManyRequests();

    $this->assertDatabaseMissing('users', ['email' => 'spammer11@example.com']);
});

it('throttles contact form spam', function () {
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->postJson('/api/v1/contact', [
            'name' => 'Spammer',
            'email' => "spammer{$attempt}@example.com",
            'message' => 'Buy cheap watches.',
        ])->assertOk();
    }

    $this->postJson('/api/v1/contact', [
        'name' => 'Spammer',
        'email' => 'spammer11@example.com',
        'message' => 'Buy cheap watches.',
    ])->assertTooManyRequests();
});

it('throttles checkout verification hammering', function () {
    [$buyer, $purchase] = createRateLimitBuyerWithPurchase();

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->actingAs($buyer)
            ->getJson("/api/v1/checkout/{$purchase->id}/verify")
            ->assertOk();
    }

    $this->actingAs($buyer)
        ->getJson("/api/v1/checkout/{$purchase->id}/verify")
        ->assertTooManyRequests();
});
