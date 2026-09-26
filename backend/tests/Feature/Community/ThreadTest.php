<?php

use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can list categories', function () {
    ForumCategory::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'description' => 'Test',
        'icon_emoji' => '🤔',
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/forum/categories');

    $response->assertStatus(200)
        ->assertJsonPath('data.0.name', 'Test Category');
});

it('can create a thread', function () {
    $user = User::factory()->create();
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test',
        'description' => 'Test',
        'icon_emoji' => '🤔',
        'sort_order' => 1,
    ]);

    Sanctum::actingAs($user, ['*']);
    $response = $this->postJson('/api/v1/forum/threads', [
        'title' => 'This is a valid thread title',
        'body' => 'This is a valid body that exceeds the minimum length of 20 characters.',
        'category_id' => $category->id,
        'is_anonymous' => false,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'This is a valid thread title');

    $this->assertDatabaseHas('forum_threads', [
        'title' => 'This is a valid thread title',
        'user_id' => $user->id,
    ]);
});

it('rejects a thread with title over 100 characters', function () {
    $user = User::factory()->create();
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test-title-limit',
        'description' => 'Test',
        'icon_emoji' => '🤔',
        'sort_order' => 1,
    ]);

    Sanctum::actingAs($user, ['*']);
    $response = $this->postJson('/api/v1/forum/threads', [
        'title' => str_repeat('a', 101),
        'body' => 'This is a valid body that exceeds the minimum length of 20 characters.',
        'category_id' => $category->id,
        'is_anonymous' => false,
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('forum_threads', 0);
});

it('rejects a thread with body over 5000 characters', function () {
    $user = User::factory()->create();
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test-body-limit',
        'description' => 'Test',
        'icon_emoji' => '🤔',
        'sort_order' => 1,
    ]);

    Sanctum::actingAs($user, ['*']);
    $response = $this->postJson('/api/v1/forum/threads', [
        'title' => 'This is a valid thread title',
        'body' => str_repeat('a', 5001),
        'category_id' => $category->id,
        'is_anonymous' => false,
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('forum_threads', 0);
});

it('can post a reply', function () {
    $user = User::factory()->create();
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test-2',
        'description' => 'Test',
        'icon_emoji' => '🤔',
        'sort_order' => 1,
    ]);

    $thread = ForumThread::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'This is a test thread title',
        'body' => 'This is a test body for a thread.',
        'last_activity_at' => now(),
    ]);

    Sanctum::actingAs($user, ['*']);
    $replyResponse = $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => 'This is a reply to the thread.',
    ]);

    $replyResponse->assertStatus(201)
        ->assertJsonPath('data.body', 'This is a reply to the thread.');

    $this->assertDatabaseHas('forum_replies', [
        'thread_id' => $thread->id,
        'body' => 'This is a reply to the thread.',
    ]);
});

it('rejects a reply with body over 5000 characters', function () {
    $user = User::factory()->create();
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test-reply-limit',
        'description' => 'Test',
        'icon_emoji' => '🤔',
        'sort_order' => 1,
    ]);

    $thread = ForumThread::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'This is a test thread title',
        'body' => 'This is a test body for a thread.',
        'last_activity_at' => now(),
    ]);

    Sanctum::actingAs($user, ['*']);
    $response = $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => str_repeat('a', 5001),
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('forum_replies', 0);
});
