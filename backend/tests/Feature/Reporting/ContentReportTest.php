<?php

use App\Constants\ReportingConstants;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Shared\Actions\SubmitContentReportAction;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Domain\Shared\Services\ContentTargetResolver;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function contentReportThread(User $owner, array $overrides = []): ForumThread
{
    $suffix = uniqid();

    $category = ForumCategory::create([
        'name' => 'Reports '.$suffix,
        'slug' => 'reports-'.$suffix,
        'description' => 'Content report tests',
        'icon_emoji' => '🚩',
        'sort_order' => 1,
    ]);

    return ForumThread::create(array_merge([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'title' => 'A thread that can be reported',
        'body' => 'Thread body that is long enough for content report tests.',
        'last_activity_at' => now(),
    ], $overrides));
}

function contentReportReply(ForumThread $thread, User $owner, array $overrides = []): ForumReply
{
    return ForumReply::create(array_merge([
        'thread_id' => $thread->id,
        'user_id' => $owner->id,
        'body' => 'A reply body that is long enough for report tests.',
    ], $overrides));
}

function contentReportContract(User $farmer, array $overrides = []): ForwardContract
{
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Report Farm '.$farmer->id.uniqid()]);
    $plot = Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Plot A',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10,
    ]);
    $recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);

    return ForwardContract::factory()->create(array_merge([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ], $overrides));
}

function contentReportListing(User $farmer, array $overrides = []): HarvestListing
{
    return HarvestListing::create(array_merge([
        'farmer_id' => $farmer->id,
        'title' => 'Fresh rice for sale',
        'description' => 'Newly harvested rice, ready for pickup.',
        'crop_name' => 'Rice',
        'quantity_kg' => 100,
        'price_per_kg' => 30.00,
        'total_price' => 3000.00,
        'estimated_harvest_date' => now()->addDays(7)->toDateString(),
        'expiry_date' => now()->addDays(30)->toDateString(),
        'status' => ContractStatus::AVAILABLE,
    ], $overrides));
}

function contentReportPayload(string $type, int|string $id, array $overrides = []): array
{
    return array_merge([
        'reportable_type' => $type,
        'reportable_id' => $id,
        'reason' => ContentReportReason::SPAM->value,
    ], $overrides);
}

it('creates a receipt for a reported thread', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id));

    $response->assertCreated();
    expect(array_keys($response->json('data')))->toBe(['id', 'status']);
    expect($response->json('data.id'))->toBeString();
    expect($response->json('data.status'))->toBe(ContentReportStatus::OPEN->value);
    expect(ContentReport::count())->toBe(1);
});

it('creates a receipt for a reported reply', function () {
    $reporter = User::factory()->farmer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);
    $reply = contentReportReply($thread, $owner);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/reports', contentReportPayload('reply', $reply->id));

    $response->assertCreated();
    expect($response->json('data.id'))->toBeString();
    expect($response->json('data.status'))->toBe(ContentReportStatus::OPEN->value);
});

it('creates a receipt for a reported contract', function () {
    $reporter = User::factory()->buyer()->create();
    $farmer = User::factory()->farmer()->create();
    $contract = contentReportContract($farmer);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/reports', contentReportPayload('contract', $contract->id));

    $response->assertCreated();
    expect($response->json('data.id'))->toBeString();
    expect($response->json('data.status'))->toBe(ContentReportStatus::OPEN->value);
});

it('creates a receipt for a reported listing', function () {
    $reporter = User::factory()->buyer()->create();
    $farmer = User::factory()->farmer()->create();
    $listing = contentReportListing($farmer);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/reports', contentReportPayload('listing', $listing->id));

    $response->assertCreated();
    expect($response->json('data.id'))->toBeString();
    expect($response->json('data.status'))->toBe(ContentReportStatus::OPEN->value);
});

it('creates a receipt for a reported demand', function () {
    $reporter = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $demand = ReverseMarketplaceHelper::makeDemand($buyer);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/reports', contentReportPayload('demand', $demand->id));

    $response->assertCreated();
    expect($response->json('data.id'))->toBeString();
    expect($response->json('data.status'))->toBe(ContentReportStatus::OPEN->value);
});

it('accepts the platform-only fraud and prohibited-item reasons', function () {
    $reporter = User::factory()->buyer()->create();
    $farmer = User::factory()->farmer()->create();
    $contract = contentReportContract($farmer);
    $listing = contentReportListing($farmer);

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('contract', $contract->id, [
        'reason' => ContentReportReason::SUSPECTED_FRAUD->value,
    ]))->assertCreated();

    $this->postJson('/api/v1/reports', contentReportPayload('listing', $listing->id, [
        'reason' => ContentReportReason::PROHIBITED_ITEM->value,
    ]))->assertCreated();
});

it('rejects submitted class names and unknown types with 422', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    foreach (['App\\Domain\\Community\\Models\\ForumThread', 'User', 'user'] as $type) {
        $this->postJson('/api/v1/reports', contentReportPayload($type, $thread->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reportable_type']);
    }

    expect(ContentReport::count())->toBe(0);
});

it('rejects invalid target ids with 422', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    foreach (['abc', 0, -5, '1.5'] as $id) {
        $this->postJson('/api/v1/reports', contentReportPayload('thread', $id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reportable_id']);
    }

    expect(ContentReport::count())->toBe(0);
});

it('rejects unknown reasons with 422', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, ['reason' => 'bad-vibes']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);

    expect(ContentReport::count())->toBe(0);
});

it('returns 404 for missing targets of every type', function () {
    $reporter = User::factory()->buyer()->create();

    Sanctum::actingAs($reporter, ['*']);

    foreach (['thread', 'reply', 'contract', 'listing', 'demand'] as $type) {
        $this->postJson('/api/v1/reports', contentReportPayload($type, 999999))
            ->assertNotFound();
    }

    expect(ContentReport::count())->toBe(0);
});

it('returns 404 for inaccessible forum targets', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();

    $deletedThread = contentReportThread($owner);
    $deletedThread->delete();

    $thread = contentReportThread($owner);
    $orphanedReply = contentReportReply($thread, $owner);
    $thread->delete();

    $liveThread = contentReportThread($owner);
    $deletedParent = contentReportReply($liveThread, $owner);
    $childReply = contentReportReply($liveThread, $owner, ['parent_id' => $deletedParent->id]);
    $deletedParent->delete();

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $deletedThread->id))->assertNotFound();
    $this->postJson('/api/v1/reports', contentReportPayload('reply', $orphanedReply->id))->assertNotFound();
    $this->postJson('/api/v1/reports', contentReportPayload('reply', $childReply->id))->assertNotFound();

    expect(ContentReport::count())->toBe(0);
});

it('rejects self-reports on every target type with 422', function () {
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();

    $thread = contentReportThread($farmer);
    $reply = contentReportReply($thread, $farmer);
    $contract = contentReportContract($farmer);
    $listing = contentReportListing($farmer);
    $demand = ReverseMarketplaceHelper::makeDemand($buyer);

    Sanctum::actingAs($farmer, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id))->assertStatus(422);
    $this->postJson('/api/v1/reports', contentReportPayload('reply', $reply->id))->assertStatus(422);
    $this->postJson('/api/v1/reports', contentReportPayload('contract', $contract->id))->assertStatus(422);
    $this->postJson('/api/v1/reports', contentReportPayload('listing', $listing->id))->assertStatus(422);

    Auth::forgetGuards();
    Sanctum::actingAs($buyer, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('demand', $demand->id))->assertStatus(422);

    expect(ContentReport::count())->toBe(0);
});

it('enforces the verified member role matrix', function () {
    $farmer = User::factory()->farmer()->create();
    $buyer = User::factory()->buyer()->create();
    $unverified = User::factory()->farmer()->unverified()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $demand = ReverseMarketplaceHelper::makeDemand($buyer);
    $thread = contentReportThread($farmer);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id))->assertUnauthorized();

    Sanctum::actingAs($unverified, ['*']);
    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id))->assertForbidden();

    Auth::forgetGuards();
    Sanctum::actingAs($admin, ['*']);
    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id))->assertForbidden();

    Auth::forgetGuards();
    Sanctum::actingAs($farmer, ['*']);
    $this->postJson('/api/v1/reports', contentReportPayload('demand', $demand->id))->assertCreated();

    Auth::forgetGuards();
    Sanctum::actingAs($buyer, ['*']);
    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id))->assertCreated();
});

it('rejects suspended reporters through the shared backstop', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);
    $reporter->forceFill(['suspended_at' => now(), 'suspended_reason' => 'test suspension'])->save();

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id))
        ->assertForbidden()
        ->assertJsonPath('code', 'account_suspended');
});

it('bounds the report description at 1000 characters', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'description' => str_repeat('a', ReportingConstants::DESCRIPTION_MAX_LENGTH),
    ]))->assertCreated();

    Auth::forgetGuards();
    $secondReporter = User::factory()->buyer()->create();
    Sanctum::actingAs($secondReporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'description' => str_repeat('a', ReportingConstants::DESCRIPTION_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);
});

it('requires a description when the reason is other', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'reason' => ContentReportReason::OTHER->value,
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'reason' => ContentReportReason::OTHER->value,
        'description' => '   ',
    ]))->assertStatus(422)->assertJsonValidationErrors(['description']);

    $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'reason' => ContentReportReason::OTHER->value,
        'description' => 'x',
    ]))->assertCreated();

    expect(ContentReport::count())->toBe(1);
});

it('stores a bounded server-built snapshot while keeping the receipt minimal', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner, ['body' => str_repeat('b', 5000)]);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'description' => 'Needs review.',
    ]));

    $response->assertCreated();
    expect(array_keys($response->json('data')))->toBe(['id', 'status']);

    $snapshot = ContentReport::firstOrFail()->target_snapshot;

    expect($snapshot['type'])->toBe('thread')
        ->and($snapshot['id'])->toBe((string) $thread->id)
        ->and($snapshot['owner_id'])->toBe((string) $owner->id)
        ->and(mb_strlen($snapshot['title']))->toBeLessThanOrEqual(ReportingConstants::SNAPSHOT_FIELD_MAX_LENGTH)
        ->and(mb_strlen($snapshot['excerpt']))->toBe(ReportingConstants::SNAPSHOT_FIELD_MAX_LENGTH)
        ->and($snapshot['excerpt'])->toBe(str_repeat('b', ReportingConstants::SNAPSHOT_FIELD_MAX_LENGTH))
        ->and($snapshot['captured_at'])->not->toBeEmpty();
});

it('captures owner, title, excerpt, and business status per target type', function () {
    $reporter = User::factory()->buyer()->create();
    $farmer = User::factory()->farmer()->create();

    $thread = contentReportThread($farmer, ['title' => 'Parent thread title']);
    $reply = contentReportReply($thread, $farmer, ['body' => 'Reply body for the snapshot.']);
    $contract = contentReportContract($farmer);
    $demand = ReverseMarketplaceHelper::makeDemand(User::factory()->buyer()->create());

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/reports', contentReportPayload('reply', $reply->id))->assertCreated();
    $this->postJson('/api/v1/reports', contentReportPayload('contract', $contract->id))->assertCreated();

    Auth::forgetGuards();
    Sanctum::actingAs($farmer, ['*']);
    $this->postJson('/api/v1/reports', contentReportPayload('demand', $demand->id))->assertCreated();

    $replySnapshot = ContentReport::where('reportable_type', ReportTargetType::REPLY->value)->firstOrFail()->target_snapshot;
    $contractSnapshot = ContentReport::where('reportable_type', ReportTargetType::CONTRACT->value)->firstOrFail()->target_snapshot;
    $demandSnapshot = ContentReport::where('reportable_type', ReportTargetType::DEMAND->value)->firstOrFail()->target_snapshot;

    expect($replySnapshot['title'])->toBe('Parent thread title')
        ->and($replySnapshot['excerpt'])->toBe('Reply body for the snapshot.')
        ->and($replySnapshot['status'])->toBeNull()
        ->and($contractSnapshot['status'])->toBe(ContractStatus::AVAILABLE->value)
        ->and($contractSnapshot['owner_id'])->toBe((string) $farmer->id)
        ->and($demandSnapshot['status'])->toBe('open')
        ->and($demandSnapshot['owner_id'])->toBe((string) $demand->buyer_id);
});

it('returns the existing receipt unchanged for duplicate reports', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $first = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'reason' => ContentReportReason::SPAM->value,
        'description' => 'Original description.',
    ]));

    $first->assertCreated();

    $second = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id, [
        'reason' => ContentReportReason::HARASSMENT->value,
        'description' => 'Changed description.',
    ]));

    $second->assertOk()->assertJsonPath('data.id', $first->json('data.id'));

    $report = ContentReport::firstOrFail();

    expect(ContentReport::count())->toBe(1)
        ->and($report->reason)->toBe(ContentReportReason::SPAM)
        ->and($report->description)->toBe('Original description.');
});

it('returns the existing receipt after a terminal decision without reopening it', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $first = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id));
    $first->assertCreated();

    DB::table('content_reports')->where('id', $first->json('data.id'))->update([
        'status' => ContentReportStatus::RESOLVED->value,
        'version' => 2,
        'outcome' => 'hidden',
    ]);

    $second = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id));

    $second->assertOk()
        ->assertJsonPath('data.id', $first->json('data.id'))
        ->assertJsonPath('data.status', ContentReportStatus::RESOLVED->value);

    $report = ContentReport::firstOrFail();

    expect(ContentReport::count())->toBe(1)
        ->and($report->status)->toBe(ContentReportStatus::RESOLVED)
        ->and($report->version)->toBe(2);
});

it('returns the existing receipt after the target is deleted', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $first = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id));
    $first->assertCreated();

    $thread->delete();

    $second = $this->postJson('/api/v1/reports', contentReportPayload('thread', $thread->id));

    $second->assertOk()->assertJsonPath('data.id', $first->json('data.id'));

    expect(ContentReport::count())->toBe(1);
});

it('limits report creation to five requests per minute', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $payload = contentReportPayload('thread', $thread->id);

    $this->postJson('/api/v1/reports', $payload)->assertCreated();

    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/v1/reports', $payload)->assertOk();
    }

    $this->postJson('/api/v1/reports', $payload)->assertOk();
    $this->postJson('/api/v1/reports', $payload)->assertStatus(429);
});

it('shares one creation limit across the new and legacy report routes', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $payload = contentReportPayload('thread', $thread->id);

    $this->postJson('/api/v1/reports', $payload)->assertCreated();
    $this->postJson('/api/v1/forum/reports', $payload)->assertOk();
    $this->postJson('/api/v1/reports', $payload)->assertOk();
    $this->postJson('/api/v1/forum/reports', $payload)->assertOk();
    $this->postJson('/api/v1/reports', $payload)->assertOk();
    $this->postJson('/api/v1/forum/reports', $payload)->assertStatus(429);
});

it('limits report creation to twenty requests per day', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    Sanctum::actingAs($reporter, ['*']);

    $payload = contentReportPayload('thread', $thread->id);

    try {
        for ($batch = 0; $batch < 4; $batch++) {
            for ($i = 0; $i < 5; $i++) {
                $response = $this->postJson('/api/v1/reports', $payload);

                if ($batch === 0 && $i === 0) {
                    $response->assertCreated();
                } else {
                    $response->assertOk();
                }
            }

            $this->travel(61)->seconds();
        }

        $this->postJson('/api/v1/reports', $payload)->assertStatus(429);
    } finally {
        $this->travelBack();
    }
});

it('creates a single row for parallel submissions of the same report', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the parallel submission test.');
    }

    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);
    $threadId = (string) $thread->id;
    $reporterId = $reporter->id;

    DB::commit();

    $pids = [];
    $failures = 0;

    try {
        for ($i = 0; $i < 2; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Could not fork a parallel submission process.');
            }

            if ($pid === 0) {
                try {
                    DB::purge();
                    $childReporter = User::whereKey($reporterId)->firstOrFail();
                    app(SubmitContentReportAction::class)->execute(
                        $childReporter,
                        ReportTargetType::THREAD,
                        $threadId,
                        ContentReportReason::SPAM,
                        null
                    );
                    exit(0);
                } catch (Throwable $e) {
                    fwrite(STDERR, 'parallel report failed: '.$e->getMessage());
                    exit(1);
                }
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);

            if (pcntl_wexitstatus($status) !== 0) {
                $failures++;
            }
        }

        expect($failures)->toBe(0);
        expect(DB::table('content_reports')->where('user_id', $reporterId)->count())->toBe(1);
    } finally {
        DB::table('content_reports')->where('user_id', $reporterId)->delete();
        ForumThread::withTrashed()->where('id', $threadId)->forceDelete();
        DB::table('users')->whereIn('id', [$reporterId, $owner->id])->delete();
        DB::purge();

        if (DB::transactionLevel() === 0) {
            DB::beginTransaction();
        }
    }
});

it('recovers the existing row when a concurrent insert wins the unique key', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    $existing = ContentReport::create([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);

    $conflict = new QueryException(
        'pgsql',
        'insert into "content_reports" ("user_id", "reportable_type", "reportable_id") values (?, ?, ?)',
        [$reporter->id, ReportTargetType::THREAD->value, $thread->id],
        new Exception('duplicate key value violates unique constraint "content_reports_user_id_reportable_type_reportable_id_unique"', 23505)
    );

    $repository = Mockery::mock(ContentReportRepositoryInterface::class);
    $repository->shouldReceive('findByReporterTarget')->twice()->andReturn(null, $existing);
    $repository->shouldReceive('create')->once()->andThrow($conflict);

    $result = (new SubmitContentReportAction($repository, app(ContentTargetResolver::class)))
        ->execute($reporter, ReportTargetType::THREAD, (string) $thread->id, ContentReportReason::SPAM, null);

    expect($result->id)->toBe($existing->id);
    expect(ContentReport::count())->toBe(1);
});

it('rethrows non-unique database failures instead of recovering', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = contentReportThread($owner);

    $failure = new QueryException(
        'pgsql',
        'insert into "content_reports" ("user_id") values (?)',
        [$reporter->id],
        new Exception('insert or update on table "content_reports" violates foreign key constraint', 23503)
    );

    $repository = Mockery::mock(ContentReportRepositoryInterface::class);
    $repository->shouldReceive('findByReporterTarget')->once()->andReturn(null);
    $repository->shouldReceive('create')->once()->andThrow($failure);

    $action = new SubmitContentReportAction($repository, app(ContentTargetResolver::class));

    expect(fn () => $action->execute(
        $reporter,
        ReportTargetType::THREAD,
        (string) $thread->id,
        ContentReportReason::SPAM,
        null
    ))->toThrow(QueryException::class);
});
