<?php

use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createProfileFarmerChain(): array
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

    return [$farmer, $recommendation];
}

function createProfileCategory(): ForumCategory
{
    return ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test',
        'description' => 'Test',
        'icon_emoji' => '🌾',
        'sort_order' => 1,
    ]);
}

it('shows a farmer profile with sales stats and public posts only', function () {
    [$farmer, $recommendation] = createProfileFarmerChain();
    $viewer = User::factory()->buyer()->create();

    ForwardContract::factory()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'status' => ContractStatus::SOLD,
        'total_price' => 5000,
    ]);
    ForwardContract::factory()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
        'status' => ContractStatus::AVAILABLE,
    ]);
    HarvestListing::create([
        'farmer_id' => $farmer->id,
        'title' => 'Sold Corn',
        'crop_name' => 'Corn',
        'quantity_kg' => 100,
        'price_per_kg' => 15,
        'total_price' => 1500,
        'estimated_harvest_date' => now()->addDays(10)->format('Y-m-d'),
        'expiry_date' => now()->addDays(9)->format('Y-m-d'),
        'status' => ContractStatus::SOLD,
    ]);

    $category = createProfileCategory();
    ForumThread::create([
        'user_id' => $farmer->id,
        'category_id' => $category->id,
        'title' => 'My public harvest tips',
        'body' => 'Rotate your crops.',
        'is_anonymous' => false,
        'last_activity_at' => now(),
    ]);
    ForumThread::create([
        'user_id' => $farmer->id,
        'category_id' => $category->id,
        'title' => 'Secret struggles',
        'body' => 'Do not attribute this.',
        'is_anonymous' => true,
        'last_activity_at' => now(),
    ]);

    $response = $this->actingAs($viewer)->getJson("/api/v1/users/{$farmer->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $farmer->id)
        ->assertJsonPath('data.name', $farmer->name)
        ->assertJsonPath('data.role', 'farmer')
        ->assertJsonPath('data.stats.total_listed', 3)
        ->assertJsonPath('data.stats.total_sold', 2)
        ->assertJsonPath('data.stats.total_revenue', 6500)
        ->assertJsonCount(1, 'data.posts')
        ->assertJsonPath('data.posts.0.title', 'My public harvest tips')
        ->assertJsonMissingPath('data.email')
        ->assertJsonMissing(['title' => 'Secret struggles']);
});

it('shows a buyer profile with purchase stats', function () {
    [$farmer, $recommendation] = createProfileFarmerChain();
    $buyer = User::factory()->buyer()->create();

    $contract = ForwardContract::factory()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);
    Purchase::factory()->completed()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
        'amount_paid' => 2500,
    ]);
    Purchase::factory()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
        'payment_status' => PaymentStatus::PENDING,
        'amount_paid' => 9999,
    ]);

    $response = $this->actingAs($farmer)->getJson("/api/v1/users/{$buyer->id}");

    $response->assertOk()
        ->assertJsonPath('data.role', 'buyer')
        ->assertJsonPath('data.stats.total_purchases', 1)
        ->assertJsonPath('data.stats.total_spent', 2500);
});

it('rejects unauthenticated profile requests', function () {
    $farmer = User::factory()->farmer()->create();

    $this->getJson("/api/v1/users/{$farmer->id}")->assertUnauthorized();
});

it('returns 404 for a missing profile', function () {
    $viewer = User::factory()->buyer()->create();

    $this->actingAs($viewer)->getJson('/api/v1/users/999999')->assertNotFound();
});

it('shows anonymous posts on your own profile', function () {
    [$farmer] = createProfileFarmerChain();
    $category = createProfileCategory();

    ForumThread::create([
        'user_id' => $farmer->id,
        'category_id' => $category->id,
        'title' => 'My anonymous post',
        'body' => 'Posted anonymously.',
        'is_anonymous' => true,
        'last_activity_at' => now(),
    ]);

    $response = $this->actingAs($farmer)->getJson("/api/v1/users/{$farmer->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data.posts')
        ->assertJsonPath('data.posts.0.title', 'My anonymous post');
});
