<?php

use App\Constants\ChatConstants;
use App\Domain\Chat\Models\ChatConversation;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->sender = User::factory()->create();
    $peer = User::factory()->create();
    $this->conversation = ChatConversation::create();
    $this->conversation->participants()->createMany([
        ['user_id' => $this->sender->id],
        ['user_id' => $peer->id],
    ]);
    $this->url = "/api/v1/chat/conversations/{$this->conversation->id}/messages";
    Sanctum::actingAs($this->sender, ['*']);
});

it('accepts a message at the max length', function () {
    $this->postJson($this->url, [
        'body' => str_repeat('a', ChatConstants::MESSAGE_MAX_LENGTH),
    ])->assertCreated();

    $this->assertDatabaseCount('chat_messages', 1);
});

it('rejects a message one character over the max', function () {
    $this->postJson($this->url, [
        'body' => str_repeat('a', ChatConstants::MESSAGE_MAX_LENGTH + 1),
    ])->assertStatus(422)->assertJsonValidationErrors(['body']);

    $this->assertDatabaseCount('chat_messages', 0);
});

it('rejects an empty message', function () {
    $this->postJson($this->url, [
        'body' => '',
    ])->assertStatus(422)->assertJsonValidationErrors(['body']);
});
