<?php

use App\Constants\ForumConstants;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    $this->category = ForumCategory::create([
        'name' => 'Boundary',
        'slug' => 'boundary-'.uniqid(),
        'description' => 'Boundary tests',
        'icon_emoji' => '📏',
        'sort_order' => 1,
    ]);
    $this->threadPayload = [
        'title' => 'A valid thread title here',
        'body' => 'A valid thread body that is well over twenty characters long.',
        'category_id' => $this->category->id,
    ];
    $this->makeThread = function (): ForumThread {
        return ForumThread::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'A thread for reply boundaries',
            'body' => 'Thread body that is long enough to pass validation.',
            'last_activity_at' => now(),
        ]);
    };
});

it('accepts a thread title at min and max lengths', function () {
    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'title' => str_repeat('a', ForumConstants::TITLE_MIN_LENGTH),
    ]))->assertCreated();

    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'title' => str_repeat('b', ForumConstants::TITLE_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a thread title below min and above max', function () {
    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'title' => str_repeat('a', ForumConstants::TITLE_MIN_LENGTH - 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['title']);

    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'title' => str_repeat('a', ForumConstants::TITLE_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('accepts a thread body at min and max lengths', function () {
    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'body' => str_repeat('a', ForumConstants::BODY_MIN_LENGTH),
    ]))->assertCreated();

    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'body' => str_repeat('b', ForumConstants::BODY_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects a thread body below min and above max', function () {
    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'body' => str_repeat('a', ForumConstants::BODY_MIN_LENGTH - 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['body']);

    $this->postJson('/api/v1/forum/threads', array_merge($this->threadPayload, [
        'body' => str_repeat('a', ForumConstants::BODY_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['body']);
});

it('accepts a reply at min and max lengths', function () {
    $thread = ($this->makeThread)();

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => str_repeat('a', ForumConstants::REPLY_MIN_LENGTH),
    ])->assertCreated();

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => str_repeat('b', ForumConstants::REPLY_MAX_LENGTH),
    ])->assertCreated();
});

it('rejects a reply below min and above max', function () {
    $thread = ($this->makeThread)();

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => str_repeat('a', ForumConstants::REPLY_MIN_LENGTH - 1),
    ])->assertStatus(422)->assertJsonValidationErrors(['body']);

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => str_repeat('a', ForumConstants::REPLY_MAX_LENGTH + 1),
    ])->assertStatus(422)->assertJsonValidationErrors(['body']);
});
