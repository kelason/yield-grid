<?php

use App\Domain\Chat\Models\ChatConversation;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createFarmerWithContract(): array
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

    return [$farmer, $contract];
}

it('can start a conversation with a transaction partner', function () {
    [$farmer, $contract] = createFarmerWithContract();
    $buyer = User::factory()->buyer()->create();
    Purchase::factory()->completed()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
    ]);

    Sanctum::actingAs($buyer, ['*']);
    $response = $this->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $farmer->id,
    ]);

    $response->assertStatus(201);

    $conversationId = $response->json('data.id');

    $this->assertDatabaseHas('chat_participants', [
        'conversation_id' => $conversationId,
        'user_id' => $buyer->id,
    ]);

    $this->assertDatabaseHas('chat_participants', [
        'conversation_id' => $conversationId,
        'user_id' => $farmer->id,
    ]);
});

it('can start a conversation over a harvest listing purchase', function () {
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
    Purchase::factory()->completed()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => null,
        'harvest_listing_id' => $listing->id,
    ]);

    Sanctum::actingAs($farmer, ['*']);
    $response = $this->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $buyer->id,
    ]);

    $response->assertStatus(201);
});

it('blocks starting a conversation without a transaction', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Sanctum::actingAs($user1, ['*']);
    $response = $this->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $user2->id,
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseCount('chat_conversations', 0);
});

it('blocks starting a conversation when the only purchase failed', function () {
    [$farmer, $contract] = createFarmerWithContract();
    $buyer = User::factory()->buyer()->create();
    Purchase::factory()->failed()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
    ]);

    Sanctum::actingAs($buyer, ['*']);
    $response = $this->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $farmer->id,
    ]);

    $response->assertStatus(403);
});

it('allows starting a conversation while payment is still pending', function () {
    [$farmer, $contract] = createFarmerWithContract();
    $buyer = User::factory()->buyer()->create();
    Purchase::factory()->create([
        'buyer_id' => $buyer->id,
        'forward_contract_id' => $contract->id,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    Sanctum::actingAs($buyer, ['*']);
    $response = $this->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $farmer->id,
    ]);

    $response->assertStatus(201);
});

it('rejects a message over 500 words', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $conversation = ChatConversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $user1->id],
        ['user_id' => $user2->id],
    ]);

    Sanctum::actingAs($user1, ['*']);
    $response = $this->postJson("/api/v1/chat/conversations/{$conversation->id}/messages", [
        'body' => str_repeat('word ', 501),
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('chat_messages', 0);
});

it('can send a message', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $conversation = ChatConversation::create();
    $conversation->participants()->createMany([
        ['user_id' => $user1->id],
        ['user_id' => $user2->id],
    ]);

    Sanctum::actingAs($user1, ['*']);
    $response = $this->postJson("/api/v1/chat/conversations/{$conversation->id}/messages", [
        'body' => 'Hello there!',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.body', 'Hello there!');

    $this->assertDatabaseHas('chat_messages', [
        'conversation_id' => $conversation->id,
        'user_id' => $user1->id,
        'body' => 'Hello there!',
    ]);
});

it('can list conversations for a user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $conversation = ChatConversation::create(['last_message_at' => now()]);
    $conversation->participants()->createMany([
        ['user_id' => $user1->id],
        ['user_id' => $user2->id],
    ]);

    Sanctum::actingAs($user1, ['*']);
    $response = $this->getJson('/api/v1/chat/conversations');

    $response->assertStatus(200)
        ->assertJsonPath('data.0.id', $conversation->id);
});
