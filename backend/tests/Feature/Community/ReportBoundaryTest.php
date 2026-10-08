<?php

use App\Constants\ForumConstants;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->owner = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    $category = ForumCategory::create([
        'name' => 'Reports',
        'slug' => 'reports-'.uniqid(),
        'description' => 'Report boundary tests',
        'icon_emoji' => '🚩',
        'sort_order' => 1,
    ]);
    $this->thread = ForumThread::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'title' => 'A thread to report on',
        'body' => 'Thread body that is long enough to pass validation.',
        'last_activity_at' => now(),
    ]);
    $this->payload = [
        'reportable_type' => 'thread',
        'reportable_id' => $this->thread->id,
        'reason' => 'spam',
    ];
});

it('accepts a report description at the max length', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'description' => str_repeat('a', ForumConstants::REPORT_DESCRIPTION_MAX_LENGTH),
    ]))->assertOk();
});

it('rejects a report description one character over the max', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'description' => str_repeat('a', ForumConstants::REPORT_DESCRIPTION_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);
});

it('rejects an unknown reportable type', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reportable_type' => 'user',
    ]))->assertStatus(422)->assertJsonValidationErrors(['reportable_type']);
});

it('rejects an unknown report reason', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reason' => 'bad-vibes',
    ]))->assertStatus(422)->assertJsonValidationErrors(['reason']);
});

it('keeps the legacy payload and success message while storing a unified report', function () {
    $this->postJson('/api/v1/forum/reports', $this->payload)
        ->assertOk()
        ->assertExactJson(['message' => 'Report submitted successfully.']);

    $report = ContentReport::firstOrFail();

    expect($report->reportable_type)->toBe(ReportTargetType::THREAD)
        ->and($report->reason)->toBe(ContentReportReason::SPAM)
        ->and($report->status)->toBe(ContentReportStatus::OPEN)
        ->and($report->target_snapshot)->not->toBeNull()
        ->and($report->target_snapshot['id'])->toBe((string) $this->thread->id);
});

it('returns the legacy message for duplicate reports without creating another row', function () {
    $this->postJson('/api/v1/forum/reports', $this->payload)->assertOk();
    $this->postJson('/api/v1/forum/reports', $this->payload)
        ->assertOk()
        ->assertExactJson(['message' => 'Report submitted successfully.']);

    expect(ContentReport::count())->toBe(1);
});

it('rejects platform-only reasons and types on the legacy adapter', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reason' => ContentReportReason::SUSPECTED_FRAUD->value,
    ]))->assertStatus(422)->assertJsonValidationErrors(['reason']);

    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reportable_type' => ReportTargetType::CONTRACT->value,
    ]))->assertStatus(422)->assertJsonValidationErrors(['reportable_type']);

    expect(ContentReport::count())->toBe(0);
});

it('checks target existence and ownership on the legacy adapter', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reportable_id' => 999999,
    ]))->assertNotFound();

    $ownThread = ForumThread::create([
        'user_id' => $this->user->id,
        'category_id' => $this->thread->category_id,
        'title' => 'The reporter owns this thread',
        'body' => 'Thread body that is long enough to pass validation.',
        'last_activity_at' => now(),
    ]);

    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reportable_id' => $ownThread->id,
    ]))->assertStatus(422);

    expect(ContentReport::count())->toBe(0);
});

it('requires a description for other on the legacy adapter', function () {
    $this->postJson('/api/v1/forum/reports', array_merge($this->payload, [
        'reason' => ContentReportReason::OTHER->value,
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);
});
