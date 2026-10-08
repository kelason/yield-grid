<?php

use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Community\Events\NewReplyPosted;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Shared\Enums\ReportTargetType;
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

it('returns 404 for hidden anonymous threads to everyone', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $author = User::factory()->create();
    $thread = createAnonymousTestThread($author);
    $stranger = User::factory()->create();

    app(ModerateForumContentAction::class)->execute(
        $admin, ReportTargetType::THREAD, (string) $thread->id, true, 'spam content'
    );

    $this->actingAs($stranger)->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();
    $this->actingAs($author)->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();
});

it('keeps anonymous authors confidential in broadcast payloads', function () {
    $author = User::factory()->create();
    $thread = createAnonymousTestThread($author, false);

    $reply = ForumReply::create([
        'thread_id' => $thread->id,
        'user_id' => $author->id,
        'body' => 'An anonymous broadcast reply.',
        'is_anonymous' => true,
    ]);

    $payload = (new NewReplyPosted($reply->id, $thread->id))->broadcastWith();

    expect($payload['reply']['author'])->toBe([
        'name' => 'Anonymous Farmer',
        'avatar_url' => null,
        'role' => 'farmer',
    ]);
    expect((string) json_encode($payload))->not->toContain($author->email);
});
