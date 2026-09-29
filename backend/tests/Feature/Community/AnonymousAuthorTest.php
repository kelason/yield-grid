<?php

use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createAnonymousTestThread(User $author, bool $isAnonymous = true): ForumThread
{
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test',
        'description' => 'Test',
        'icon_emoji' => '🌾',
        'sort_order' => 1,
    ]);

    return ForumThread::create([
        'user_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'A test thread title',
        'body' => 'A test body for a thread.',
        'is_anonymous' => $isAnonymous,
        'last_activity_at' => now(),
    ]);
}

it('shows the author their own identity on their anonymous thread', function () {
    $author = User::factory()->create();
    $thread = createAnonymousTestThread($author);

    $this->actingAs($author)
        ->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.author.id', $author->id)
        ->assertJsonPath('data.author.name', $author->name);
});

it('hides the author identity on anonymous threads from other users', function () {
    $author = User::factory()->create();
    $thread = createAnonymousTestThread($author);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.author.name', 'Anonymous Farmer')
        ->assertJsonMissingPath('data.author.id');
});

it('shows the author their own identity on their anonymous reply', function () {
    $author = User::factory()->create();
    $thread = createAnonymousTestThread($author, false);

    $this->actingAs($author)->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => 'An anonymous reply body.',
        'is_anonymous' => true,
    ])->assertCreated();

    $this->actingAs($author)
        ->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.replies.0.author.id', $author->id)
        ->assertJsonPath('data.replies.0.author.name', $author->name);
});

it('hides the author identity on anonymous replies from other users', function () {
    $author = User::factory()->create();
    $thread = createAnonymousTestThread($author, false);

    $this->actingAs($author)->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => 'An anonymous reply body.',
        'is_anonymous' => true,
    ])->assertCreated();

    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.replies.0.author.name', 'Anonymous Farmer')
        ->assertJsonMissingPath('data.replies.0.author.id');
});
