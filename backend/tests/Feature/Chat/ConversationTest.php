<?php

use App\Domain\Chat\Models\ChatConversation;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can start a conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Sanctum::actingAs($user1, ['*']);
    $response = $this->postJson('/api/v1/chat/conversations', [
        'recipient_id' => $user2->id,
    ]);

    $response->assertStatus(201);

    $conversationId = $response->json('data.id');

    $this->assertDatabaseHas('chat_participants', [
        'conversation_id' => $conversationId,
        'user_id' => $user1->id,
    ]);

    $this->assertDatabaseHas('chat_participants', [
        'conversation_id' => $conversationId,
        'user_id' => $user2->id,
    ]);
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
