<?php

use App\Domain\Community\Actions\CreateReplyAction;
use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Community\DTOs\CreateReplyData;
use App\Domain\Community\Events\NewReplyPosted;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\AdminActionLog;
use App\Domain\Shared\Models\ContentReport;
use App\Policies\ForumContentPolicy;
use Domain\Users\Models\User;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function modvisCategory(): ForumCategory
{
    $suffix = uniqid();

    return ForumCategory::create([
        'name' => 'Modvis '.$suffix,
        'slug' => 'modvis-'.$suffix,
        'description' => 'Moderation visibility tests',
        'icon_emoji' => '🛡️',
        'sort_order' => 1,
    ]);
}

function modvisThread(User $author, array $overrides = []): ForumThread
{
    return ForumThread::create(array_merge([
        'user_id' => $author->id,
        'category_id' => modvisCategory()->id,
        'title' => 'A visible thread title here',
        'body' => 'A thread body long enough for validation rules.',
        'last_activity_at' => now(),
    ], $overrides));
}

function modvisReply(ForumThread $thread, User $author, array $overrides = []): ForumReply
{
    $reply = ForumReply::create(array_merge([
        'thread_id' => $thread->id,
        'user_id' => $author->id,
        'body' => 'A reply body for visibility tests.',
    ], $overrides));

    $thread->increment('reply_count');

    return $reply;
}

function modvisAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function modvisHideThread(User $admin, ForumThread $thread, string $reason = 'spam content'): ForumThread
{
    /** @var ForumThread $hidden */
    $hidden = app(ModerateForumContentAction::class)->execute(
        $admin, ReportTargetType::THREAD, (string) $thread->id, true, $reason
    );

    return $hidden;
}

function modvisHideReply(User $admin, ForumReply $reply, string $reason = 'spam content'): ForumReply
{
    /** @var ForumReply $hidden */
    $hidden = app(ModerateForumContentAction::class)->execute(
        $admin, ReportTargetType::REPLY, (string) $reply->id, true, $reason
    );

    return $hidden;
}

function modvisRestore(User $admin, ReportTargetType $type, int $id): void
{
    app(ModerateForumContentAction::class)->execute($admin, $type, (string) $id, false, 'appeal upheld');
}

final class ModvisSpyBroadcaster extends Broadcaster
{
    /** @var array<int, array{channels: array, event: string, payload: array}> */
    public array $sent = [];

    public function auth($request)
    {
        //
    }

    public function validAuthenticationResponse($request, $result)
    {
        return [];
    }

    public function broadcast(array $channels, $event, array $payload = []): void
    {
        $this->sent[] = ['channels' => $channels, 'event' => $event, 'payload' => $payload];
    }
}

function modvisRunBroadcastJob(NewReplyPosted $event): ModvisSpyBroadcaster
{
    $spy = new ModvisSpyBroadcaster;

    config([
        'broadcasting.default' => 'modvis-spy',
        'broadcasting.connections.modvis-spy' => ['driver' => 'modvis-spy'],
    ]);
    Broadcast::extend('modvis-spy', fn () => $spy);

    (new BroadcastEvent($event))->handle(app(BroadcastFactory::class));

    return $spy;
}

function modvisRaceCleanup(array $userIds, array $threadIds): void
{
    DB::table('forum_replies')->whereIn('thread_id', $threadIds)->delete();
    DB::table('forum_threads')->whereIn('id', $threadIds)->delete();
    DB::table('admin_action_logs')->where('subject_type', 'thread')->whereIn('subject_id', array_map(strval(...), $threadIds))->delete();
    DB::table('admin_action_logs')->where('subject_type', 'reply')->delete();
    DB::table('users')->whereIn('id', $userIds)->delete();
    DB::table('forum_categories')->where('slug', 'like', 'modvis-%')->delete();
    DB::purge('pgsql_race');

    if (DB::transactionLevel() === 0) {
        DB::beginTransaction();
    }
}

it('hides a thread and records reversible history', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);

    $hidden = modvisHideThread($admin, $thread, 'spam content');

    expect($hidden->hidden_at)->not->toBeNull()
        ->and($hidden->hidden_by)->toBe($admin->id)
        ->and($hidden->hidden_reason)->toBe('spam content');

    $log = AdminActionLog::where('subject_type', 'thread')->where('subject_id', (string) $thread->id)->firstOrFail();

    expect($log->action)->toBe(AdminAction::CONTENT_HIDDEN)
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->reason)->toBe('spam content')
        ->and($log->before['hidden_at'])->toBeNull()
        ->and($log->after['hidden_at'])->not->toBeNull()
        ->and($log->related_report_id)->toBeNull();

    $this->assertDatabaseHas('forum_threads', ['id' => $thread->id]);
});

it('restores a hidden thread without touching other rows', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    modvisHideThread($admin, $thread);

    modvisRestore($admin, ReportTargetType::THREAD, $thread->id);

    $restored = $thread->fresh();

    expect($restored->hidden_at)->toBeNull()
        ->and($restored->hidden_by)->toBeNull()
        ->and($restored->hidden_reason)->toBeNull()
        ->and(AdminActionLog::where('action', AdminAction::CONTENT_RESTORED->value)->count())->toBe(1);
});

it('rejects repeated hide and restore states', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $reply = modvisReply($thread, $author);
    $action = app(ModerateForumContentAction::class);

    $action->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, 'spam');

    expect(fn () => $action->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, 'spam'))
        ->toThrow(LogicException::class);
    expect(fn () => $action->execute($admin, ReportTargetType::REPLY, (string) $reply->id, false, 'appeal'))
        ->toThrow(LogicException::class);

    expect(AdminActionLog::count())->toBe(1);
});

it('rejects marketplace types and invalid reasons', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $action = app(ModerateForumContentAction::class);

    foreach ([ReportTargetType::CONTRACT, ReportTargetType::LISTING, ReportTargetType::DEMAND] as $type) {
        expect(fn () => $action->execute($admin, $type, (string) $thread->id, true, 'spam'))
            ->toThrow(InvalidArgumentException::class);
    }

    expect(fn () => $action->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, '   '))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $action->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, str_repeat('r', 501)))
        ->toThrow(InvalidArgumentException::class);

    $action->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, 'x');
    $action->execute($admin, ReportTargetType::THREAD, (string) $thread->id, false, str_repeat('r', 500));

    expect($thread->fresh()->hidden_at)->toBeNull();
    expect(AdminActionLog::count())->toBe(2);
});

it('excludes hidden threads from the list and restores them', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $viewer = User::factory()->buyer()->create();
    $hidden = modvisThread($author, ['title' => 'Hidden thread title here']);
    $visible = modvisThread($author, ['title' => 'Visible thread title here']);

    modvisHideThread($admin, $hidden);

    Sanctum::actingAs($viewer, ['*']);

    $this->getJson('/api/v1/forum/threads')
        ->assertOk()
        ->assertJsonMissing(['title' => 'Hidden thread title here'])
        ->assertJsonFragment(['title' => 'Visible thread title here']);

    modvisRestore($admin, ReportTargetType::THREAD, $hidden->id);

    $this->getJson('/api/v1/forum/threads')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Hidden thread title here']);
});

it('returns 404 for hidden thread detail until restored', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $viewer = User::factory()->buyer()->create();
    $thread = modvisThread($author);

    modvisHideThread($admin, $thread);

    Sanctum::actingAs($viewer, ['*']);
    $this->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();

    Sanctum::actingAs($author, ['*']);
    $this->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();

    modvisRestore($admin, ReportTargetType::THREAD, $thread->id);

    $this->getJson("/api/v1/forum/threads/{$thread->id}")->assertOk();
});

it('suppresses hidden replies and hidden-parent descendants in detail', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $viewer = User::factory()->buyer()->create();
    $thread = modvisThread($author);
    $kept = modvisReply($thread, $author, ['body' => 'kept visible reply body']);
    $parent = modvisReply($thread, $author, ['body' => 'hidden parent reply body']);
    $child = modvisReply($thread, $author, ['body' => 'suppressed child reply body', 'parent_id' => $parent->id]);

    modvisHideReply($admin, $parent);

    Sanctum::actingAs($viewer, ['*']);

    $response = $this->getJson("/api/v1/forum/threads/{$thread->id}")->assertOk();

    $response->assertJsonFragment(['body' => 'kept visible reply body']);
    $response->assertJsonMissing(['body' => 'hidden parent reply body']);
    $response->assertJsonMissing(['body' => 'suppressed child reply body']);
    expect($child->fresh()->hidden_at)->toBeNull();
});

it('keeps accepted answer and counts consistent with visible replies', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $viewer = User::factory()->buyer()->create();
    $thread = modvisThread($author);
    $accepted = modvisReply($thread, $author, ['body' => 'accepted reply body here']);
    modvisReply($thread, $author, ['body' => 'plain visible reply body']);

    $thread->update(['accepted_reply_id' => $accepted->id]);
    $accepted->update(['is_accepted' => true]);

    modvisHideReply($admin, $accepted);

    Sanctum::actingAs($viewer, ['*']);

    $this->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.reply_count', 1)
        ->assertJsonPath('data.has_accepted_reply', false);

    $this->getJson('/api/v1/forum/threads')
        ->assertOk()
        ->assertJsonPath('data.0.reply_count', 1)
        ->assertJsonPath('data.0.has_accepted_reply', false);

    modvisRestore($admin, ReportTargetType::REPLY, $accepted->id);

    $this->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.reply_count', 2)
        ->assertJsonPath('data.has_accepted_reply', true);

    expect($accepted->fresh()->is_accepted)->toBeTrue();
});

it('never restores soft-deleted records through visibility restore', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $viewer = User::factory()->buyer()->create();
    $thread = modvisThread($author);
    $deleted = modvisReply($thread, $author, ['body' => 'deleted reply body here']);
    modvisReply($thread, $author, ['body' => 'surviving reply body here']);
    $deleted->delete();

    modvisHideThread($admin, $thread);
    modvisRestore($admin, ReportTargetType::THREAD, $thread->id);

    expect($deleted->fresh()->trashed())->toBeTrue();

    Sanctum::actingAs($viewer, ['*']);

    $this->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.reply_count', 1)
        ->assertJsonMissing(['body' => 'deleted reply body here']);
});

it('treats orphaned replies as invisible while null-parent replies stay visible', function () {
    $author = User::factory()->farmer()->create();
    $viewer = User::factory()->buyer()->create();
    $thread = modvisThread($author);
    $topLevel = modvisReply($thread, $author, ['body' => 'top level reply body']);
    $parent = modvisReply($thread, $author, ['body' => 'doomed parent reply body']);
    $orphan = modvisReply($thread, $author, ['body' => 'orphaned child reply body', 'parent_id' => $parent->id]);
    $parent->delete();

    expect(ForumContentPolicy::isReplyVisible($topLevel->fresh()))->toBeTrue();
    expect(ForumContentPolicy::isReplyVisible($orphan->fresh()))->toBeFalse();

    Sanctum::actingAs($viewer, ['*']);

    $this->getJson("/api/v1/forum/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.reply_count', 1)
        ->assertJsonFragment(['body' => 'top level reply body'])
        ->assertJsonMissing(['body' => 'orphaned child reply body']);
});

it('excludes hidden threads from profiles for owners and viewers', function () {
    $admin = modvisAdmin();
    [$farmer, $viewer] = [User::factory()->farmer()->create(), User::factory()->buyer()->create()];
    $hidden = modvisThread($farmer, ['title' => 'Hidden profile thread title']);
    modvisThread($farmer, ['title' => 'Visible profile thread title']);

    modvisHideThread($admin, $hidden);

    $this->actingAs($viewer)->getJson("/api/v1/users/{$farmer->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.posts')
        ->assertJsonPath('data.posts.0.title', 'Visible profile thread title');

    $this->actingAs($farmer)->getJson("/api/v1/users/{$farmer->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.posts');
});

it('returns 404 for anonymous hidden threads without leaking identity', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $stranger = User::factory()->buyer()->create();
    $thread = modvisThread($author, ['is_anonymous' => true]);

    modvisHideThread($admin, $thread);

    $this->actingAs($stranger)->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();
    $this->actingAs($author)->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();
});

it('blocks direct replies votes acceptance edits and attachments on hidden content', function () {
    Storage::fake('public');
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $member = User::factory()->buyer()->create();
    $thread = modvisThread($author);
    $reply = modvisReply($thread, $author, ['body' => 'hidden reply body here']);

    modvisHideThread($admin, $thread);
    modvisHideReply($admin, $reply);

    Sanctum::actingAs($member, ['*']);

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", ['body' => 'sneaky reply body'])->assertNotFound();
    $this->postJson("/api/v1/forum/threads/{$thread->id}/vote", ['value' => 1])->assertNotFound();
    $this->postJson("/api/v1/forum/replies/{$reply->id}/vote", ['value' => 1])->assertNotFound();

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('hidden.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => $thread->id,
    ])->assertNotFound();

    expect(ForumReply::count())->toBe(1);

    Sanctum::actingAs($author, ['*']);

    $this->putJson("/api/v1/forum/threads/{$thread->id}", ['title' => 'Edited hidden title here', 'body' => 'Edited body long enough for validation.'])->assertNotFound();
    $this->putJson("/api/v1/forum/replies/{$reply->id}", ['body' => 'edited hidden reply body'])->assertNotFound();
    $this->postJson("/api/v1/forum/replies/{$reply->id}/accept")->assertNotFound();
});

it('rejects reply parents from another thread', function () {
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $other = modvisThread($author);
    $foreignParent = modvisReply($other, $author);

    Sanctum::actingAs($author, ['*']);

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => 'cross-thread parent reply body',
        'parent_id' => $foreignParent->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['parent_id']);

    expect(ForumReply::count())->toBe(1);
});

it('rejects hidden parents and missing threads for new replies', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $parent = modvisReply($thread, $author);
    modvisHideReply($admin, $parent);

    Sanctum::actingAs($author, ['*']);

    $this->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
        'body' => 'child of hidden parent body',
        'parent_id' => $parent->id,
    ])->assertNotFound();

    expect(ForumReply::count())->toBe(1);
});

it('checks attachment ownership and target access', function () {
    Storage::fake('public');
    $author = User::factory()->farmer()->create();
    $stranger = User::factory()->buyer()->create();
    $thread = modvisThread($author);

    Sanctum::actingAs($stranger, ['*']);

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('theirs.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => $thread->id,
    ])->assertForbidden();

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('ghost.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => 999999,
    ])->assertNotFound();

    Sanctum::actingAs($author, ['*']);

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('mine.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => $thread->id,
    ])->assertCreated();
});

it('preserves owner delete on hidden content', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $reply = modvisReply($thread, $author);

    modvisHideThread($admin, $thread);
    modvisHideReply($admin, $reply);

    Sanctum::actingAs($author, ['*']);

    $this->deleteJson("/api/v1/forum/replies/{$reply->id}")->assertNoContent();
    $this->deleteJson("/api/v1/forum/threads/{$thread->id}")->assertNoContent();

    expect($reply->fresh()->trashed())->toBeTrue();
    expect($thread->fresh()->trashed())->toBeTrue();
});

it('hides hidden content from non-owner delete attempts', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $stranger = User::factory()->buyer()->create();
    $thread = modvisThread($author);
    $reply = modvisReply($thread, $author);

    modvisHideThread($admin, $thread);
    modvisHideReply($admin, $reply);

    Sanctum::actingAs($stranger, ['*']);

    $this->deleteJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();
    $this->deleteJson("/api/v1/forum/replies/{$reply->id}")->assertNotFound();

    expect($thread->fresh()->trashed())->toBeFalse();
    expect($reply->fresh()->trashed())->toBeFalse();
});

it('serializes reply creation behind an uncommitted thread hide', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);

    DB::commit();

    try {
        config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
        $race = DB::connection('pgsql_race');
        $race->beginTransaction();

        try {
            $race->table('forum_threads')->where('id', $thread->id)->lockForUpdate()->first();
            $race->table('forum_threads')->where('id', $thread->id)->update(['hidden_at' => now()]);

            DB::statement("SET lock_timeout = '2s'");

            try {
                app(CreateReplyAction::class)->execute(new CreateReplyData(
                    threadId: $thread->id,
                    userId: $author->id,
                    body: 'race reply body here',
                    isAnonymous: false,
                ));
                $this->fail('Reply creation slipped past the hide thread lock.');
            } catch (QueryException $e) {
                expect($e->getMessage())->toContain('55P03');
            } finally {
                DB::statement('SET lock_timeout = 0');
            }

            expect(DB::table('forum_replies')->where('thread_id', $thread->id)->count())->toBe(0);
        } finally {
            $race->rollBack();
        }

        $reply = app(CreateReplyAction::class)->execute(new CreateReplyData(
            threadId: $thread->id,
            userId: $author->id,
            body: 'race reply body here',
            isAnonymous: false,
        ));

        expect($reply->thread_id)->toBe($thread->id);
        expect(DB::table('forum_replies')->where('thread_id', $thread->id)->count())->toBe(1);
    } finally {
        modvisRaceCleanup([$admin->id, $author->id], [$thread->id]);
    }
});

it('serializes thread hide behind a held thread lock', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);

    DB::commit();

    try {
        config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
        $race = DB::connection('pgsql_race');
        $race->beginTransaction();

        try {
            $race->table('forum_threads')->where('id', $thread->id)->lockForUpdate()->first();

            DB::statement("SET lock_timeout = '2s'");

            try {
                app(ModerateForumContentAction::class)->execute(
                    $admin->fresh(), ReportTargetType::THREAD, (string) $thread->id, true, 'racing hide'
                );
                $this->fail('Hide slipped past the held thread lock.');
            } catch (QueryException $e) {
                expect($e->getMessage())->toContain('55P03');
            } finally {
                DB::statement('SET lock_timeout = 0');
            }

            expect($thread->fresh()->hidden_at)->toBeNull();
            expect(AdminActionLog::count())->toBe(0);
        } finally {
            $race->rollBack();
        }

        app(ModerateForumContentAction::class)->execute(
            $admin->fresh(), ReportTargetType::THREAD, (string) $thread->id, true, 'racing hide'
        );

        expect($thread->fresh()->hidden_at)->not->toBeNull();
    } finally {
        modvisRaceCleanup([$admin->id, $author->id], [$thread->id]);
    }
});

it('authorizes thread channel subscriptions against visibility and suspension', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $member = User::factory()->buyer()->create();
    $visible = modvisThread($author);
    $hidden = modvisThread($author);
    modvisHideThread($admin, $hidden);

    expect(ForumContentPolicy::canSubscribeToThread($member, (string) $visible->id))->toBeTrue();
    expect(ForumContentPolicy::canSubscribeToThread(null, (string) $visible->id))->toBeFalse();
    expect(ForumContentPolicy::canSubscribeToThread($member, (string) $hidden->id))->toBeFalse();
    expect(ForumContentPolicy::canSubscribeToThread($member, '999999'))->toBeFalse();

    $member->forceFill(['suspended_at' => now(), 'suspended_reason' => 'spam'])->save();

    expect(ForumContentPolicy::canSubscribeToThread($member->fresh(), (string) $visible->id))->toBeFalse();

    $this->actingAs($member->fresh())->postJson('/api/v1/broadcasting/auth', [
        'channel_name' => "private-thread.{$visible->id}",
        'socket_id' => '1.1',
    ])->assertForbidden();
});

it('broadcasts nothing once content is hidden before evaluation', function () {
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $reply = modvisReply($thread, $author, ['body' => 'broadcast reply body here']);

    $event = new NewReplyPosted($reply->id, $thread->id);

    expect($event->broadcastOn())->toHaveCount(1);

    $spy = modvisRunBroadcastJob($event);

    expect($spy->sent)->toHaveCount(1);
    expect($spy->sent[0]['payload']['reply']['body'])->toBe('broadcast reply body here');

    modvisHideThread($admin, $thread);

    $lateEvent = new NewReplyPosted($reply->id, $thread->id);

    expect($lateEvent->broadcastOn())->toBe([]);
    expect($lateEvent->broadcastWith())->toBe([]);

    $lateSpy = modvisRunBroadcastJob($lateEvent);

    expect($lateSpy->sent)->toBe([]);

    modvisHideReply($admin, $reply->fresh());

    $replyEvent = new NewReplyPosted($reply->id, $thread->id);

    expect($replyEvent->broadcastOn())->toBe([]);
    expect($replyEvent->broadcastWith())->toBe([]);
});

it('sanitizes broadcast payloads and never exposes raw models', function () {
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);
    $anonymous = modvisReply($thread, $author, ['body' => 'anonymous broadcast body', 'is_anonymous' => true]);
    $named = modvisReply($thread, $author, ['body' => 'named broadcast body here']);

    $anonymousPayload = (new NewReplyPosted($anonymous->id, $thread->id))->broadcastWith();
    $namedPayload = (new NewReplyPosted($named->id, $thread->id))->broadcastWith();

    expect($anonymousPayload['reply']['author'])->toBe([
        'name' => 'Anonymous Farmer',
        'avatar_url' => null,
        'role' => 'farmer',
    ]);
    expect($namedPayload['reply']['author']['id'])->toBe($author->id);
    expect($namedPayload['reply']['author']['name'])->toBe($author->name);

    foreach ([$anonymousPayload, $namedPayload] as $payload) {
        expect((string) json_encode($payload))->not->toContain($author->email);

        foreach (['user_id', 'email', 'hidden_at', 'hidden_by', 'hidden_reason'] as $leakyKey) {
            expect(array_key_exists($leakyKey, $payload['reply']))->toBeFalse();
        }
    }

    $publicProps = array_map(
        fn (ReflectionProperty $prop): string => $prop->getName(),
        (new ReflectionClass(NewReplyPosted::class))->getProperties(ReflectionProperty::IS_PUBLIC)
    );

    expect($publicProps)->toContain('replyId');
    expect($publicProps)->toContain('threadId');
    expect($publicProps)->not->toContain('reply');
});

it('keeps stored media after hide while removing in-app links', function () {
    Storage::fake('public');
    $admin = modvisAdmin();
    $author = User::factory()->farmer()->create();
    $thread = modvisThread($author);

    Sanctum::actingAs($author, ['*']);

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('evidence.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => $thread->id,
    ])->assertCreated();

    $path = DB::table('forum_attachments')->where('attachable_id', $thread->id)->value('file_path');

    expect($path)->not->toBeNull();

    modvisHideThread($admin, $thread);

    // Hide removes in-app content and links only; known storage URLs are untouched.
    expect(Storage::disk('public')->exists($path))->toBeTrue();
    $this->getJson("/api/v1/forum/threads/{$thread->id}")->assertNotFound();
});

it('rejects new reports on hidden forum content', function () {
    $admin = modvisAdmin();
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $thread = modvisThread($owner);
    $reply = modvisReply($thread, $owner);

    modvisHideThread($admin, $thread);
    modvisHideReply($admin, $reply);

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', [
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
    ])->assertNotFound();

    $this->postJson('/api/v1/reports', [
        'reportable_type' => ReportTargetType::REPLY->value,
        'reportable_id' => $reply->id,
        'reason' => ContentReportReason::SPAM->value,
    ])->assertNotFound();

    expect(ContentReport::count())->toBe(0);
});

it('returns the unchanged receipt for duplicate reports after hiding', function () {
    $admin = modvisAdmin();
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $thread = modvisThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $payload = [
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
    ];

    $first = $this->postJson('/api/v1/reports', $payload)->assertCreated();

    modvisHideThread($admin, $thread);

    $second = $this->postJson('/api/v1/reports', $payload)->assertOk();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
    expect(ContentReport::count())->toBe(1);
});
