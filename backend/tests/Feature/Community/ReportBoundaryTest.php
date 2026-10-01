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
    $category = ForumCategory::create([
        'name' => 'Reports',
        'slug' => 'reports-'.uniqid(),
        'description' => 'Report boundary tests',
        'icon_emoji' => '🚩',
        'sort_order' => 1,
    ]);
    $this->thread = ForumThread::create([
        'user_id' => $this->user->id,
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
