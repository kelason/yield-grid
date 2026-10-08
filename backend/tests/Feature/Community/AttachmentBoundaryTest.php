<?php

use App\Constants\ForumConstants;
use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Shared\Enums\ReportTargetType;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('accepts a small image attachment', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('plot.jpg', 100, 100),
        'attachable_type' => 'thread',
    ])->assertCreated();
});

it('rejects a non-image attachment', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        'attachable_type' => 'thread',
    ])->assertStatus(422)->assertJsonValidationErrors(['file']);
});

it('rejects an attachment over the max size', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->create(
            'huge.png',
            ForumConstants::ATTACHMENT_MAX_SIZE_KB + 1,
            'image/png'
        ),
        'attachable_type' => 'thread',
    ])->assertStatus(422)->assertJsonValidationErrors(['file']);
});

it('rejects an unknown attachable type', function () {
    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('plot.jpg', 100, 100),
        'attachable_type' => 'user',
    ])->assertStatus(422)->assertJsonValidationErrors(['attachable_type']);
});

it('rejects attachments to hidden or foreign threads', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $author = User::factory()->create();
    $category = ForumCategory::create([
        'name' => 'Test',
        'slug' => 'test-attachment-access',
        'description' => 'Test',
        'icon_emoji' => '📎',
        'sort_order' => 1,
    ]);
    $thread = ForumThread::create([
        'user_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'A thread for attachment checks',
        'body' => 'Thread body that is long enough to pass validation.',
        'last_activity_at' => now(),
    ]);

    app(ModerateForumContentAction::class)->execute(
        $admin, ReportTargetType::THREAD, (string) $thread->id, true, 'spam content'
    );

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('hidden.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => $thread->id,
    ])->assertNotFound();

    $visible = ForumThread::create([
        'user_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'Another thread for attachment checks',
        'body' => 'Thread body that is long enough to pass validation.',
        'last_activity_at' => now(),
    ]);

    $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/forum/attachments', [
        'file' => UploadedFile::fake()->image('foreign.jpg', 100, 100),
        'attachable_type' => 'thread',
        'attachable_id' => $visible->id,
    ])->assertForbidden();
});
