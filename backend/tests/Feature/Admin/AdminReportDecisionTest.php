<?php

declare(strict_types=1);

use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Shared\Actions\DecideContentReportAction;
use App\Domain\Shared\Actions\SubmitContentReportAction;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\AdminActionLog;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Domain\Shared\Services\ContentTargetResolver;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function ardAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function ardTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('ard-test-token')->plainTextToken;
}

function ardThread(User $owner, array $overrides = []): ForumThread
{
    $suffix = uniqid();

    $category = ForumCategory::create([
        'name' => 'Decisions '.$suffix,
        'slug' => 'decisions-'.$suffix,
        'description' => 'Report decision tests',
        'icon_emoji' => '⚖️',
        'sort_order' => 1,
    ]);

    return ForumThread::create(array_merge([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'title' => 'A thread under admin review',
        'body' => 'Thread body that is long enough for decision tests.',
        'last_activity_at' => now(),
    ], $overrides));
}

function ardReply(ForumThread $thread, User $owner, array $overrides = []): ForumReply
{
    return ForumReply::create(array_merge([
        'thread_id' => $thread->id,
        'user_id' => $owner->id,
        'body' => 'A reply body that is long enough for decision tests.',
    ], $overrides));
}

function ardThreadReport(User $reporter, ForumThread $thread, array $overrides = []): ContentReport
{
    return ContentReport::create(array_merge([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
        'target_snapshot' => app(ContentTargetResolver::class)->snapshot($thread),
    ], $overrides));
}

function ardContract(User $farmer, array $overrides = []): ForwardContract
{
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Decision Farm '.$farmer->id.uniqid()]);
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

function ardDecision(array $overrides = []): array
{
    return array_merge([
        'status' => ContentReportStatus::REVIEWING->value,
        'note' => 'Looking into this report.',
        'expected_version' => 1,
    ], $overrides);
}

it('denies guests on the report review endpoints', function () {
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread($owner));

    $this->getJson('/api/v1/admin/reports')->assertUnauthorized();
    $this->getJson("/api/v1/admin/reports/{$report->id}")->assertUnauthorized();
    $this->getJson('/api/v1/admin/reports/999999')->assertUnauthorized();
    $this->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision())->assertUnauthorized();
    $this->postJson('/api/v1/admin/reports/999999/decision', ardDecision())->assertUnauthorized();
});

it('denies farmers and buyers on the report review endpoints including missing ids', function () {
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread($owner));

    $farmerToken = ardTokenFor(User::factory()->farmer()->create());
    $this->withToken($farmerToken)->getJson('/api/v1/admin/reports')->assertForbidden();
    $this->withToken($farmerToken)->getJson("/api/v1/admin/reports/{$report->id}")->assertForbidden();
    $this->withToken($farmerToken)->getJson('/api/v1/admin/reports/999999')->assertForbidden();
    $this->withToken($farmerToken)->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision())->assertForbidden();
    $this->withToken($farmerToken)->postJson('/api/v1/admin/reports/999999/decision', ardDecision())->assertForbidden();

    $buyerToken = ardTokenFor(User::factory()->buyer()->create());
    $this->withToken($buyerToken)->getJson('/api/v1/admin/reports')->assertForbidden();
    $this->withToken($buyerToken)->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision())->assertForbidden();
});

it('denies unverified admins on the report review endpoints', function () {
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread($owner));

    $token = ardTokenFor(User::factory()->unverified()->create(['role' => 'admin']));

    $this->withToken($token)->getJson('/api/v1/admin/reports')->assertForbidden();
    $this->withToken($token)->getJson("/api/v1/admin/reports/{$report->id}")->assertForbidden();
    $this->withToken($token)->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision())->assertForbidden();
});

it('allows a verified admin to list and view reports without storing them', function () {
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread($owner));
    $token = ardTokenFor(ardAdmin());

    $index = $this->withToken($token)->getJson('/api/v1/admin/reports');

    $index->assertOk();
    $index->assertHeader('Cache-Control', 'no-store, private');
    expect(array_keys($index->json()))->toContain('data', 'links', 'meta');
    expect(collect($index->json('data'))->pluck('id'))->toContain((string) $report->id);

    $show = $this->withToken($token)->getJson("/api/v1/admin/reports/{$report->id}");

    $show->assertOk();
    $show->assertHeader('Cache-Control', 'no-store, private');
    expect($show->json('data.id'))->toBe((string) $report->id);
});

it('returns 404 for missing reports after admin checks pass', function () {
    $token = ardTokenFor(ardAdmin());

    $this->withToken($token)->getJson('/api/v1/admin/reports/999999')->assertNotFound();
    $this->withToken($token)->postJson('/api/v1/admin/reports/999999/decision', ardDecision())->assertNotFound();
});

it('transitions an open report to reviewing with a full updated echo and history', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread(User::factory()->farmer()->create()));
    $token = ardTokenFor($admin);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['note' => 'Taking a closer look.'])
    );

    $response->assertOk();
    $response->assertHeader('Cache-Control', 'no-store, private');

    $data = $response->json('data');
    expect($data['id'])->toBe((string) $report->id);
    expect($data['status'])->toBe(ContentReportStatus::REVIEWING->value);
    expect($data['version'])->toBe(2);
    expect($data['outcome'])->toBeNull();
    expect($data['resolution_note'])->toBe('Taking a closer look.');
    expect($data['reviewed_by'])->toBe((string) $admin->id);
    expect($data['reviewed_at'])->not->toBeNull();
    expect($data['reporter']['id'])->toBe((string) $reporter->id);
    expect($data['target_snapshot'])->not->toBeNull();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe(ContentReportStatus::REVIEWING);
    expect($fresh->version)->toBe(2);
    expect($fresh->target_snapshot)->toBe($report->target_snapshot);

    $logs = AdminActionLog::where('related_report_id', $report->id)->get();
    expect($logs)->toHaveCount(1);
    expect($logs->first()->action)->toBe(AdminAction::REPORT_DECIDED);
    expect($logs->first()->subject_type)->toBe('content_report');
    expect($logs->first()->subject_id)->toBe((string) $report->id);
    expect($logs->first()->reason)->toBe('Taking a closer look.');
    expect($logs->first()->before)->toBe(['status' => 'open', 'version' => 1, 'outcome' => null]);
    expect($logs->first()->after)->toBe(['status' => 'reviewing', 'version' => 2, 'outcome' => null]);
});

it('resolves a report with no_action without touching content visibility', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport($reporter, $thread);
    $token = ardTokenFor($admin);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'no_action', 'note' => 'Content is fine.'])
    );

    $response->assertOk();
    expect($response->json('data.status'))->toBe('resolved');
    expect($response->json('data.outcome'))->toBe('no_action');
    expect($response->json('data.version'))->toBe(2);
    expect($thread->fresh()->hidden_at)->toBeNull();
    expect(AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->count())->toBe(0);
    expect(AdminActionLog::where('action', AdminAction::REPORT_DECIDED->value)->count())->toBe(1);
});

it('resolves a report with hidden by atomically hiding content, report, and history', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport($reporter, $thread);
    $token = ardTokenFor($admin);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Spam thread.'])
    );

    $response->assertOk();
    expect($response->json('data.status'))->toBe('resolved');
    expect($response->json('data.outcome'))->toBe('hidden');
    expect($response->json('data.version'))->toBe(2);

    $hidden = $thread->fresh();
    expect($hidden->hidden_at)->not->toBeNull();
    expect($hidden->hidden_by)->toBe($admin->id);
    expect($hidden->hidden_reason)->toBe('Spam thread.');

    $fresh = $report->fresh();
    expect($fresh->status)->toBe(ContentReportStatus::RESOLVED);
    expect($fresh->outcome)->toBe('hidden');

    $hideLogs = AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->get();
    expect($hideLogs)->toHaveCount(1);
    expect($hideLogs->first()->subject_type)->toBe('thread');
    expect($hideLogs->first()->subject_id)->toBe((string) $thread->id);

    $decisionLogs = AdminActionLog::where('action', AdminAction::REPORT_DECIDED->value)->get();
    expect($decisionLogs)->toHaveCount(1);
    expect($decisionLogs->first()->related_report_id)->toBe($report->id);
});

it('dismisses an open report', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread(User::factory()->farmer()->create()));
    $token = ardTokenFor($admin);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'dismissed', 'note' => 'Not a violation.'])
    );

    $response->assertOk();
    expect($response->json('data.status'))->toBe('dismissed');
    expect($report->fresh()->status)->toBe(ContentReportStatus::DISMISSED);
});

it('transitions a reviewing report to resolved and dismissed', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $toResolve = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner), [
        'status' => ContentReportStatus::REVIEWING->value,
        'version' => 2,
    ]);

    $resolved = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$toResolve->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'no_action', 'expected_version' => 2])
    );
    $resolved->assertOk();
    expect($resolved->json('data.version'))->toBe(3);

    $toDismiss = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner), [
        'status' => ContentReportStatus::REVIEWING->value,
        'version' => 2,
    ]);

    $dismissed = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$toDismiss->id}/decision",
        ardDecision(['status' => 'dismissed', 'expected_version' => 2])
    );
    $dismissed->assertOk();
    expect($dismissed->json('data.version'))->toBe(3);
});

it('rejects forbidden transitions with 409 and leaves terminal reports untouched', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $cases = [
        [ContentReportStatus::OPEN->value, 1, 'open', null],
        [ContentReportStatus::REVIEWING->value, 2, 'reviewing', null],
        [ContentReportStatus::REVIEWING->value, 2, 'open', null],
        [ContentReportStatus::RESOLVED->value, 3, 'open', null],
        [ContentReportStatus::RESOLVED->value, 3, 'reviewing', null],
        [ContentReportStatus::RESOLVED->value, 3, 'resolved', 'no_action'],
        [ContentReportStatus::RESOLVED->value, 3, 'dismissed', null],
        [ContentReportStatus::DISMISSED->value, 2, 'open', null],
        [ContentReportStatus::DISMISSED->value, 2, 'reviewing', null],
        [ContentReportStatus::DISMISSED->value, 2, 'resolved', 'hidden'],
        [ContentReportStatus::DISMISSED->value, 2, 'dismissed', null],
    ];

    foreach ($cases as [$from, $version, $to, $outcome]) {
        $report = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner), [
            'status' => $from,
            'version' => $version,
        ]);

        $payload = ardDecision(['status' => $to, 'expected_version' => $version]);

        if ($outcome !== null) {
            $payload['outcome'] = $outcome;
        }

        $this->withToken($token)
            ->postJson("/api/v1/admin/reports/{$report->id}/decision", $payload)
            ->assertConflict();

        $fresh = $report->fresh();
        expect($fresh->status->value)->toBe($from);
        expect($fresh->version)->toBe($version);
    }

    expect(AdminActionLog::count())->toBe(0);
});

it('bounds the decision note at 1 to 500 characters', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $rejected = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));

    foreach ([[], ['note' => ''], ['note' => '   '], ['note' => str_repeat('n', 501)]] as $override) {
        $payload = ardDecision();

        foreach ($override as $key => $value) {
            $payload[$key] = $value;
        }

        if ($override === []) {
            unset($payload['note']);
        }

        $this->withToken($token)
            ->postJson("/api/v1/admin/reports/{$rejected->id}/decision", $payload)
            ->assertUnprocessable();
    }

    expect($rejected->fresh()->version)->toBe(1);

    $one = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$one->id}/decision", ardDecision(['note' => 'x']))
        ->assertOk();

    $fiveHundred = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$fiveHundred->id}/decision", ardDecision(['note' => str_repeat('n', 500)]))
        ->assertOk();
});

it('requires a matching expected_version', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $report = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));

    $missing = ardDecision();
    unset($missing['expected_version']);
    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$report->id}/decision", $missing)
        ->assertUnprocessable();

    foreach ([0, -1, 'abc', 2147483648] as $badVersion) {
        $this->withToken($token)
            ->postJson(
                "/api/v1/admin/reports/{$report->id}/decision",
                ardDecision(['expected_version' => $badVersion])
            )
            ->assertUnprocessable();
    }

    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision())
        ->assertOk();

    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision(['expected_version' => 1]))
        ->assertConflict();

    expect($report->fresh()->version)->toBe(2);
});

it('echoes the current version on stale-version and terminal conflicts', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $stale = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner), [
        'status' => ContentReportStatus::REVIEWING->value,
        'version' => 2,
    ]);

    $staleConflict = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$stale->id}/decision",
        ardDecision(['expected_version' => 1])
    );
    $staleConflict->assertConflict();
    expect($staleConflict->json('message'))->not->toBeNull();
    expect($staleConflict->json('current_version'))->toBe(2);
    expect($staleConflict->json('current_version'))->toBe($stale->fresh()->version);

    $terminal = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner), [
        'status' => ContentReportStatus::RESOLVED->value,
        'version' => 3,
        'outcome' => 'no_action',
    ]);

    $terminalConflict = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$terminal->id}/decision",
        ardDecision(['status' => 'dismissed', 'expected_version' => 3])
    );
    $terminalConflict->assertConflict();
    expect($terminalConflict->json('current_version'))->toBe(3);
    expect($terminalConflict->json('current_version'))->toBe($terminal->fresh()->version);
});

it('requires an outcome only when resolving', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $missing = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$missing->id}/decision",
            ardDecision(['status' => 'resolved'])
        )
        ->assertUnprocessable();

    $bogus = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$bogus->id}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'deleted'])
        )
        ->assertUnprocessable();

    $dismissed = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$dismissed->id}/decision",
            ardDecision(['status' => 'dismissed', 'outcome' => 'hidden'])
        )
        ->assertUnprocessable();

    $reviewing = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$reviewing->id}/decision",
            ardDecision(['outcome' => 'no_action'])
        )
        ->assertUnprocessable();

    expect(ContentReport::where('status', ContentReportStatus::OPEN->value)->count())->toBe(4);
});

it('rejects missing or unknown decision statuses', function () {
    $admin = ardAdmin();
    $report = ardThreadReport(User::factory()->buyer()->create(), ardThread(User::factory()->farmer()->create()));
    $token = ardTokenFor($admin);

    $missing = ardDecision();
    unset($missing['status']);
    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$report->id}/decision", $missing)
        ->assertUnprocessable();

    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$report->id}/decision", ardDecision(['status' => 'escalated']))
        ->assertUnprocessable();
});

it('lets exactly one of two parallel decisions on one version succeed', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the parallel decision test.');
    }

    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $threadOwner = User::factory()->farmer()->create();
    $thread = ardThread($threadOwner);
    $report = ardThreadReport($reporter, $thread);
    $reportId = $report->id;
    $adminId = $admin->id;
    $reporterId = $reporter->id;
    $threadOwnerId = $threadOwner->id;
    $categoryId = $thread->category_id;
    $threadId = $thread->id;

    DB::commit();

    $pids = [];

    try {
        for ($i = 0; $i < 2; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Could not fork a parallel decision process.');
            }

            if ($pid === 0) {
                try {
                    DB::purge();
                    $childAdmin = User::whereKey($adminId)->firstOrFail();
                    $childReport = ContentReport::whereKey($reportId)->firstOrFail();
                    app(DecideContentReportAction::class)->execute(
                        $childAdmin,
                        $childReport,
                        ContentReportStatus::REVIEWING,
                        null,
                        'parallel review',
                        1
                    );
                    exit(0);
                } catch (LogicException) {
                    exit(10);
                } catch (Throwable $e) {
                    fwrite(STDERR, 'parallel decision failed: '.$e->getMessage());
                    exit(1);
                }
            }

            $pids[] = $pid;
        }

        $exits = [];

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $exits[] = pcntl_wexitstatus($status);
        }

        sort($exits);
        expect($exits)->toBe([0, 10]);
        expect(ContentReport::whereKey($reportId)->firstOrFail()->version)->toBe(2);
        expect(AdminActionLog::where('related_report_id', $reportId)->count())->toBe(1);
    } finally {
        DB::table('admin_action_logs')->where('related_report_id', $reportId)->delete();
        DB::table('content_reports')->where('id', $reportId)->delete();
        DB::table('forum_threads')->where('id', $threadId)->delete();
        DB::table('forum_categories')->where('id', $categoryId)->delete();
        DB::table('personal_access_tokens')->whereIn('tokenable_id', [$adminId, $reporterId, $threadOwnerId])->delete();
        DB::table('users')->whereIn('id', [$adminId, $reporterId, $threadOwnerId])->delete();
        DB::purge();

        if (DB::transactionLevel() === 0) {
            DB::beginTransaction();
        }
    }
});

it('performs no mutation when reviewing reports through GET', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport($reporter, $thread);
    $token = ardTokenFor($admin);

    $this->withToken($token)->getJson('/api/v1/admin/reports?status=open')->assertOk();
    $this->withToken($token)->getJson("/api/v1/admin/reports/{$report->id}")->assertOk();

    expect($report->fresh()->version)->toBe(1);
    expect($thread->fresh()->hidden_at)->toBeNull();
    expect(AdminActionLog::count())->toBe(0);
});

it('rolls back report, visibility, and history when the decision history fails', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport($reporter, $thread);

    $history = Mockery::mock(AdminActionLogRepositoryInterface::class);
    $history->shouldReceive('append')->andThrow(new RuntimeException('history unavailable'));

    $action = new DecideContentReportAction(
        app(ContentReportRepositoryInterface::class),
        app(ContentTargetResolver::class),
        app(ModerateForumContentAction::class),
        app(ModerateMarketplaceContentAction::class),
        $history,
    );

    try {
        $action->execute($admin, $report, ContentReportStatus::RESOLVED, 'hidden', 'Spam thread.', 1);
        $this->fail('The injected history failure should have aborted the decision.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('history unavailable');
    }

    expect($report->fresh()->status)->toBe(ContentReportStatus::OPEN);
    expect($report->fresh()->version)->toBe(1);
    expect($thread->fresh()->hidden_at)->toBeNull();
    expect(AdminActionLog::count())->toBe(0);
});

it('resolves an already-directly-hidden target without a duplicate hide event', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport($reporter, $thread);
    $token = ardTokenFor($admin);

    app(ModerateForumContentAction::class)->execute($admin, ReportTargetType::THREAD, (string) $thread->id, true, 'Spam thread.');
    expect(AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->count())->toBe(1);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Confirming hide.'])
    );

    $response->assertOk();
    expect($thread->fresh()->hidden_at)->not->toBeNull();
    expect(AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->count())->toBe(1);
    expect(AdminActionLog::where('action', AdminAction::REPORT_DECIDED->value)->count())->toBe(1);
});

it('gives an ancestor-hidden reply its own flag that survives thread restoration', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $thread = ardThread($owner);
    $reply = ardReply($thread, $owner);
    $token = ardTokenFor($admin);

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/thread/{$thread->id}/hide", ['reason' => 'Spam thread.'])
        ->assertOk();

    // The suppressed reply is no longer member-reportable, so the report is seeded directly.
    $report = ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::REPLY->value,
        'reportable_id' => $reply->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);

    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$report->id}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Spam reply too.'])
        )
        ->assertOk();

    expect($reply->fresh()->hidden_at)->not->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/thread/{$thread->id}/restore", ['reason' => 'Appeal upheld.'])
        ->assertOk();

    expect($thread->fresh()->hidden_at)->toBeNull();
    expect($reply->fresh()->hidden_at)->not->toBeNull();
});

it('moderates valid legacy targets that carry a null snapshot', function () {
    $admin = ardAdmin();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport(User::factory()->buyer()->create(), $thread, ['target_snapshot' => null]);
    $token = ardTokenFor($admin);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Legacy spam.'])
    );

    $response->assertOk();
    expect($thread->fresh()->hidden_at)->not->toBeNull();
    expect($report->fresh()->target_snapshot)->toBeNull();
});

it('permits review and dismissal on missing targets but rejects hiding them', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $makeMissing = fn (): ContentReport => ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => 999999,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);

    $detail = $this->withToken($token)->getJson("/api/v1/admin/reports/{$makeMissing()->id}");
    $detail->assertOk();
    expect($detail->json('data.target_available'))->toBeFalse();
    expect($detail->json('data.target'))->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$makeMissing()->id}/decision", ardDecision())
        ->assertOk();

    $dismissed = $makeMissing();
    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$dismissed->id}/decision", ardDecision(['status' => 'dismissed']))
        ->assertOk();

    $noAction = $makeMissing();
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$noAction->id}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'no_action'])
        )
        ->assertOk();

    $hidden = $makeMissing();
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$hidden->id}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'hidden'])
        )
        ->assertConflict();
    expect($hidden->fresh()->version)->toBe(1);

    $trashed = ardThread($owner);
    $trashed->delete();

    $trashedDismiss = ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $trashed->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);
    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$trashedDismiss->id}/decision", ardDecision(['status' => 'dismissed']))
        ->assertOk();

    $trashedHide = ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $trashed->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);
    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$trashedHide->id}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'hidden'])
        )
        ->assertConflict();
});

it('keeps reports terminal after the content is restored', function () {
    $admin = ardAdmin();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport(User::factory()->buyer()->create(), $thread);
    $token = ardTokenFor($admin);

    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$report->id}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Spam.'])
        )
        ->assertOk();

    $this->withToken($token)
        ->postJson("/api/v1/admin/content/thread/{$thread->id}/restore", ['reason' => 'Appeal upheld.'])
        ->assertOk();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe(ContentReportStatus::RESOLVED);
    expect($fresh->version)->toBe(2);
    expect($thread->fresh()->hidden_at)->toBeNull();
});

it('exposes reporter identity and snapshots only in admin responses', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());
    $report = ardThreadReport($reporter, $thread);
    $adminToken = ardTokenFor($admin);

    $detail = $this->withToken($adminToken)->getJson("/api/v1/admin/reports/{$report->id}");
    $detail->assertOk();
    expect($detail->json('data.reporter'))->toBe([
        'id' => (string) $reporter->id,
        'name' => $reporter->name,
        'email' => $reporter->email,
    ]);
    expect($detail->json('data.target_snapshot.type'))->toBe('thread');
    expect($detail->json('data.target_available'))->toBeTrue();
    expect($detail->json('data.target.id'))->toBe((string) $thread->id);

    $index = $this->withToken($adminToken)->getJson('/api/v1/admin/reports');
    $index->assertOk();
    $row = collect($index->json('data'))->firstWhere('id', (string) $report->id);
    expect($row['reporter']['email'])->toBe($reporter->email);
    expect($row['target_snapshot'])->not->toBeNull();

    $memberToken = ardTokenFor($reporter);
    $receipt = $this->withToken($memberToken)->postJson('/api/v1/reports', [
        'reportable_type' => 'thread',
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
    ]);
    $receipt->assertOk();
    expect(array_keys($receipt->json('data')))->toBe(['id', 'status']);
});

it('leaves the member report endpoints and snapshots unchanged', function () {
    $reporter = User::factory()->buyer()->create();
    $owner = User::factory()->farmer()->create();
    $thread = ardThread($owner);
    $token = ardTokenFor($reporter);

    $created = $this->withToken($token)->postJson('/api/v1/reports', [
        'reportable_type' => 'thread',
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
    ]);
    $created->assertCreated();
    expect(array_keys($created->json('data')))->toBe(['id', 'status']);

    $duplicate = $this->withToken($token)->postJson('/api/v1/reports', [
        'reportable_type' => 'thread',
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::HARASSMENT->value,
    ]);
    $duplicate->assertOk();
    expect($duplicate->json('data.id'))->toBe($created->json('data.id'));

    $snapshot = ContentReport::whereKey((int) $created->json('data.id'))->firstOrFail()->target_snapshot;
    expect(array_keys($snapshot))->toBe(['type', 'id', 'owner_id', 'title', 'excerpt', 'status', 'captured_at']);

    $legacy = $this->withToken($token)->postJson('/api/v1/forum/reports', [
        'reportable_type' => 'thread',
        'reportable_id' => $thread->id,
        'reason' => 'spam',
    ]);
    $legacy->assertOk();
    expect($legacy->json('message'))->toBe('Report submitted successfully.');
});

it('maps repeated-state failures to 409 and skips hides for root-hidden splits', function () {
    $admin = ardAdmin();
    $token = ardTokenFor($admin);

    // Every LogicException raised inside the decision — stale versions,
    // terminal transitions, and any repeated-state moderation failure that
    // wins a race — shares the controller catch that renders 409.
    $stale = ardThreadReport(User::factory()->buyer()->create(), ardThread(User::factory()->farmer()->create()), [
        'status' => ContentReportStatus::REVIEWING->value,
        'version' => 2,
    ]);
    $conflict = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$stale->id}/decision",
        ardDecision(['expected_version' => 1])
    );
    $conflict->assertConflict();
    expect($conflict->json('message'))->not->toBeNull();
    expect($stale->fresh()->version)->toBe(2);

    // A split clone suppressed through its hidden root resolves without a
    // duplicate hide event instead of surfacing the repeated-state 409.
    $farmer = User::factory()->farmer()->create();
    $root = ardContract($farmer);
    $clone = ardContract($farmer);
    $clone->forceFill(['moderation_root_id' => $root->id])->save();

    app(ModerateMarketplaceContentAction::class)->execute($admin, ReportTargetType::CONTRACT, (string) $root->id, true, 'Fraud.');
    expect(AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->count())->toBe(1);

    $report = ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::CONTRACT->value,
        'reportable_id' => $clone->id,
        'reason' => ContentReportReason::SUSPECTED_FRAUD->value,
        'description' => null,
    ]);

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Confirming fraud hide.'])
    );

    $response->assertOk();
    expect($report->fresh()->status)->toBe(ContentReportStatus::RESOLVED);
    expect(AdminActionLog::where('action', AdminAction::CONTENT_HIDDEN->value)->count())->toBe(1);
    expect(AdminActionLog::where('action', AdminAction::REPORT_DECIDED->value)->count())->toBe(1);
});

it('maps a repeated-state moderation race through the decision endpoint to 409', function () {
    $admin = ardAdmin();
    $farmer = User::factory()->farmer()->create();
    $contract = ardContract($farmer);
    $report = ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::CONTRACT->value,
        'reportable_id' => $contract->id,
        'reason' => ContentReportReason::SUSPECTED_FRAUD->value,
        'description' => null,
    ]);
    $token = ardTokenFor($admin);

    // The decision resolves a visible target, but a concurrent hide wins
    // between resolveTarget and the delegated hide: the repository double
    // returns the now-hidden root, so the real marketplace Action throws
    // its repeated-state LogicException through the decision controller.
    $hiddenRoot = clone $contract;
    $hiddenRoot->forceFill([
        'hidden_at' => now(),
        'hidden_by' => $admin->id,
        'hidden_reason' => 'Concurrent hide.',
    ]);

    $contracts = Mockery::mock(ForwardContractRepositoryInterface::class);
    $contracts->shouldReceive('findModerationRootLocked')->once()->with($contract->id)->andReturn($hiddenRoot);

    $this->app->bind(DecideContentReportAction::class, fn ($app) => new DecideContentReportAction(
        $app->make(ContentReportRepositoryInterface::class),
        $app->make(ContentTargetResolver::class),
        $app->make(ModerateForumContentAction::class),
        new ModerateMarketplaceContentAction(
            $app->make(AdminActionLogRepositoryInterface::class),
            $contracts,
        ),
        $app->make(AdminActionLogRepositoryInterface::class),
    ));

    $response = $this->withToken($token)->postJson(
        "/api/v1/admin/reports/{$report->id}/decision",
        ardDecision(['status' => 'resolved', 'outcome' => 'hidden', 'note' => 'Fraud confirmed.'])
    );

    $response->assertConflict();
    expect($response->json('message'))->toBe('Contract is already hidden.');
    expect($response->json('current_version'))->toBe(1);
    expect($response->json('current_version'))->toBe($report->fresh()->version);

    $fresh = $report->fresh();
    expect($fresh->status)->toBe(ContentReportStatus::OPEN);
    expect($fresh->version)->toBe(1);
    expect($contract->fresh()->hidden_at)->toBeNull();
    expect(AdminActionLog::count())->toBe(0);
});

it('renders never-valid legacy rows in the review queue without failing', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $token = ardTokenFor($admin);

    $legacyId = DB::table('content_reports')->insertGetId([
        'user_id' => $reporter->id,
        'reportable_type' => 'user',
        'reportable_id' => 7,
        'reason' => 'bad-vibes',
        'description' => 'Never-valid legacy row.',
        'status' => 'open',
        'version' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $index = $this->withToken($token)->getJson('/api/v1/admin/reports');
    $index->assertOk();
    $row = collect($index->json('data'))->firstWhere('id', (string) $legacyId);
    expect($row)->not->toBeNull();
    expect($row['reportable_type'])->toBe('user');
    expect($row['reason'])->toBe('bad-vibes');
    expect($row['target_available'])->toBeFalse();
    expect($row['target'])->toBeNull();

    $detail = $this->withToken($token)->getJson("/api/v1/admin/reports/{$legacyId}");
    $detail->assertOk();
    expect($detail->json('data.reportable_type'))->toBe('user');

    $this->withToken($token)
        ->postJson("/api/v1/admin/reports/{$legacyId}/decision", ardDecision(['status' => 'dismissed']))
        ->assertOk();

    $secondLegacyId = DB::table('content_reports')->insertGetId([
        'user_id' => $reporter->id,
        'reportable_type' => 'user',
        'reportable_id' => 8,
        'reason' => 'bad-vibes',
        'description' => null,
        'status' => 'open',
        'version' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/reports/{$secondLegacyId}/decision",
            ardDecision(['status' => 'resolved', 'outcome' => 'hidden'])
        )
        ->assertConflict();
});

it('renders a null reporter after the reporter account is deleted', function () {
    $admin = ardAdmin();
    $reporter = User::factory()->buyer()->create();
    $report = ardThreadReport($reporter, ardThread(User::factory()->farmer()->create()));
    $token = ardTokenFor($admin);

    $reporter->delete();

    $detail = $this->withToken($token)->getJson("/api/v1/admin/reports/{$report->id}");
    $detail->assertOk();
    expect($detail->json('data.reporter'))->toBeNull();
    expect($detail->json('data.id'))->toBe((string) $report->id);
});

it('filters the review queue by status, type, and reason', function () {
    $admin = ardAdmin();
    $owner = User::factory()->farmer()->create();
    $token = ardTokenFor($admin);

    $open = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner));
    $reviewing = ardThreadReport(User::factory()->buyer()->create(), ardThread($owner), [
        'status' => ContentReportStatus::REVIEWING->value,
    ]);
    $replyReport = ContentReport::create([
        'user_id' => User::factory()->buyer()->create()->id,
        'reportable_type' => ReportTargetType::REPLY->value,
        'reportable_id' => ardReply(ardThread($owner), $owner)->id,
        'reason' => ContentReportReason::HARASSMENT->value,
        'description' => null,
    ]);

    $byStatus = $this->withToken($token)->getJson('/api/v1/admin/reports?status=reviewing');
    $byStatus->assertOk();
    expect(collect($byStatus->json('data'))->pluck('id')->all())->toBe([(string) $reviewing->id]);

    $byType = $this->withToken($token)->getJson('/api/v1/admin/reports?type=reply');
    $byType->assertOk();
    expect(collect($byType->json('data'))->pluck('id')->all())->toBe([(string) $replyReport->id]);

    $byReason = $this->withToken($token)->getJson('/api/v1/admin/reports?reason=harassment');
    $byReason->assertOk();
    expect(collect($byReason->json('data'))->pluck('id')->all())->toBe([(string) $replyReport->id]);

    expect($open->fresh()->status)->toBe(ContentReportStatus::OPEN);

    $this->withToken($token)->getJson('/api/v1/admin/reports?status=escalated')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/reports?type=user')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/reports?per_page=101')->assertUnprocessable();
});

it('recovers a duplicate report inside an enclosing transaction', function () {
    $reporter = User::factory()->buyer()->create();
    $thread = ardThread(User::factory()->farmer()->create());

    $existing = ContentReport::create([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
    ]);

    // Simulates a concurrent insert winning the unique key after the
    // pre-check: the repository create performs a real duplicate insert so
    // PostgreSQL raises a genuine 23505 inside the caller's transaction.
    $repository = Mockery::mock(ContentReportRepositoryInterface::class);
    $repository->shouldReceive('findByReporterTarget')->twice()->andReturn(null, $existing);
    $repository->shouldReceive('create')->once()->andReturnUsing(
        fn (array $attributes): ContentReport => ContentReport::create($attributes)
    );

    DB::transaction(function () use ($reporter, $thread, $existing, $repository): void {
        $result = (new SubmitContentReportAction($repository, app(ContentTargetResolver::class)))
            ->execute($reporter, ReportTargetType::THREAD, (string) $thread->id, ContentReportReason::SPAM, null);

        expect($result->id)->toBe($existing->id);

        DB::table('content_reports')->where('id', $existing->id)->update(['description' => 'outer write']);

        expect(DB::table('content_reports')->where('id', $existing->id)->value('description'))->toBe('outer write');
    });
});
