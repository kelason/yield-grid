<?php

use App\Domain\Community\Models\ForumCategory;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Services\ContentTargetResolver;
use Domain\Contact\Models\ContactMessage;
use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function aemTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('aem-test-token')->plainTextToken;
}

function aemThread(User $owner, array $overrides = []): ForumThread
{
    $suffix = uniqid();

    $category = ForumCategory::create([
        'name' => 'Matrix '.$suffix,
        'slug' => 'matrix-'.$suffix,
        'description' => 'Admin matrix tests',
        'icon_emoji' => '🧭',
        'sort_order' => 1,
    ]);

    return ForumThread::create(array_merge([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'title' => 'A thread for the admin matrix',
        'body' => 'Thread body that is long enough for matrix tests.',
        'last_activity_at' => now(),
    ], $overrides));
}

function aemMessage(array $attributes = []): ContactMessage
{
    return ContactMessage::create(array_merge([
        'name' => 'Matrix Member',
        'email' => 'matrix-'.uniqid().'@example.com',
        'subject' => 'Matrix question',
        'message' => 'A matrix inquiry body.',
    ], $attributes));
}

function aemFailedReply(ContactMessage $message): ContactMessageReply
{
    return app(ContactMessageReplyRepositoryInterface::class)->create([
        'message_id' => $message->id,
        'admin_id' => null,
        'recipient' => $message->email,
        'body' => 'A reply that failed delivery.',
        'client_request_id' => (string) Str::uuid(),
        'delivery_status' => ReplyDeliveryStatus::FAILED,
        'attempts' => 3,
        'delivery_generation' => 3,
        'error_code' => 'transport_failed',
    ]);
}

function aemReport(User $reporter, ForumThread $thread): ContentReport
{
    return ContentReport::create([
        'user_id' => $reporter->id,
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SPAM->value,
        'description' => null,
        'target_snapshot' => app(ContentTargetResolver::class)->snapshot($thread),
    ]);
}

function aemTicket(User $reporter): IssueTicket
{
    return IssueTicket::create([
        'user_id' => $reporter->id,
        'category' => IssueCategory::TECHNICAL->value,
        'subject' => 'Matrix ticket subject',
        'description' => 'A matrix ticket description.',
        'page_path' => '/dashboard/marketplace',
        'status' => IssueStatus::OPEN->value,
        'client_request_id' => (string) Str::uuid(),
    ]);
}

/**
 * Every registered admin route with known and missing ids.
 *
 * @return array<string, array{string, string, array<string, mixed>}>
 */
function aemMatrix(array $ids): array
{
    $missing = 999999;
    $replyKey = (string) Str::uuid();

    return [
        'overview' => ['GET', '/api/v1/admin/overview', []],
        'users index' => ['GET', '/api/v1/admin/users', []],
        'users show known' => ['GET', "/api/v1/admin/users/{$ids['member']}", []],
        'users show missing' => ['GET', "/api/v1/admin/users/{$missing}", []],
        'users suspend known' => ['POST', "/api/v1/admin/users/{$ids['suspend']}/suspend", ['reason' => 'Matrix suspension.']],
        'users suspend missing' => ['POST', "/api/v1/admin/users/{$missing}/suspend", ['reason' => 'probe']],
        'users unsuspend known' => ['POST', "/api/v1/admin/users/{$ids['unsuspend']}/unsuspend", ['reason' => 'Matrix reinstatement.']],
        'users unsuspend missing' => ['POST', "/api/v1/admin/users/{$missing}/unsuspend", ['reason' => 'probe']],
        'inquiries index' => ['GET', '/api/v1/admin/contact-messages', []],
        'inquiries show known' => ['GET', "/api/v1/admin/contact-messages/{$ids['message']}", []],
        'inquiries show missing' => ['GET', "/api/v1/admin/contact-messages/{$missing}", []],
        'inquiries read known' => ['POST', "/api/v1/admin/contact-messages/{$ids['read']}/read", []],
        'inquiries read missing' => ['POST', "/api/v1/admin/contact-messages/{$missing}/read", []],
        'inquiries close known' => ['POST', "/api/v1/admin/contact-messages/{$ids['close']}/close", []],
        'inquiries close missing' => ['POST', "/api/v1/admin/contact-messages/{$missing}/close", []],
        'inquiries reopen known' => ['POST', "/api/v1/admin/contact-messages/{$ids['reopen']}/reopen", []],
        'inquiries reopen missing' => ['POST', "/api/v1/admin/contact-messages/{$missing}/reopen", []],
        'inquiries replies known' => ['POST', "/api/v1/admin/contact-messages/{$ids['replyTo']}/replies", ['body' => 'Matrix reply.', 'client_request_id' => $replyKey]],
        'inquiries replies missing' => ['POST', "/api/v1/admin/contact-messages/{$missing}/replies", ['body' => 'Matrix reply.', 'client_request_id' => $replyKey]],
        'inquiries retry known' => ['POST', "/api/v1/admin/contact-messages/{$ids['retryMessage']}/replies/{$ids['retry']}/retry", []],
        'inquiries retry missing' => ['POST', "/api/v1/admin/contact-messages/{$missing}/replies/{$missing}/retry", []],
        'reports index' => ['GET', '/api/v1/admin/reports', []],
        'reports show known' => ['GET', "/api/v1/admin/reports/{$ids['report']}", []],
        'reports show missing' => ['GET', "/api/v1/admin/reports/{$missing}", []],
        'reports decision known' => ['POST', "/api/v1/admin/reports/{$ids['report']}/decision", ['status' => ContentReportStatus::REVIEWING->value, 'note' => 'Matrix review.', 'expected_version' => 1]],
        'reports decision missing' => ['POST', "/api/v1/admin/reports/{$missing}/decision", ['status' => ContentReportStatus::REVIEWING->value, 'note' => 'Matrix review.', 'expected_version' => 1]],
        'issues index' => ['GET', '/api/v1/admin/issues', []],
        'issues show known' => ['GET', "/api/v1/admin/issues/{$ids['ticket']}", []],
        'issues show missing' => ['GET', "/api/v1/admin/issues/{$missing}", []],
        'issues transition known' => ['POST', "/api/v1/admin/issues/{$ids['ticket']}/transition", ['status' => IssueStatus::IN_PROGRESS->value, 'expected_version' => 1]],
        'issues transition missing' => ['POST', "/api/v1/admin/issues/{$missing}/transition", ['status' => IssueStatus::IN_PROGRESS->value, 'expected_version' => 1]],
        'content thread index' => ['GET', '/api/v1/admin/content/thread', []],
        'content reply index' => ['GET', '/api/v1/admin/content/reply', []],
        'content contract index' => ['GET', '/api/v1/admin/content/contract', []],
        'content listing index' => ['GET', '/api/v1/admin/content/listing', []],
        'content demand index' => ['GET', '/api/v1/admin/content/demand', []],
        'content show known' => ['GET', "/api/v1/admin/content/thread/{$ids['thread']}", []],
        'content show missing' => ['GET', "/api/v1/admin/content/thread/{$missing}", []],
        'content hide known' => ['POST', "/api/v1/admin/content/thread/{$ids['hide']}/hide", ['reason' => 'Matrix hide.']],
        'content hide missing' => ['POST', "/api/v1/admin/content/thread/{$missing}/hide", ['reason' => 'Matrix hide.']],
        'content restore known' => ['POST', "/api/v1/admin/content/thread/{$ids['restore']}/restore", ['reason' => 'Matrix restore.']],
        'content restore missing' => ['POST', "/api/v1/admin/content/thread/{$missing}/restore", ['reason' => 'Matrix restore.']],
        'verifications index' => ['GET', '/api/v1/admin/verifications', []],
        'verifications farm show known' => ['GET', "/api/v1/admin/verifications/farms/{$ids['showFarm']}", []],
        'verifications farm show missing' => ['GET', "/api/v1/admin/verifications/farms/{$missing}", []],
        'verifications plot show known' => ['GET', "/api/v1/admin/verifications/plots/{$ids['showPlot']}", []],
        'verifications plot show missing' => ['GET', "/api/v1/admin/verifications/plots/{$missing}", []],
        'verifications farm verify known' => ['POST', "/api/v1/admin/verifications/farms/{$ids['verifyFarm']}/verify", ['method' => VerificationMethod::FIELD_VISIT->value]],
        'verifications farm verify missing' => ['POST', "/api/v1/admin/verifications/farms/{$missing}/verify", ['method' => VerificationMethod::FIELD_VISIT->value]],
        'verifications plot reject known' => ['POST', "/api/v1/admin/verifications/plots/{$ids['rejectPlot']}/reject", ['reason' => 'Matrix reject.']],
        'verifications plot reject missing' => ['POST', "/api/v1/admin/verifications/plots/{$missing}/reject", ['reason' => 'Matrix reject.']],
        'verifications farm revoke known' => ['POST', "/api/v1/admin/verifications/farms/{$ids['revokeFarm']}/revoke", ['reason' => 'Matrix revoke.']],
        'verifications farm revoke missing' => ['POST', "/api/v1/admin/verifications/farms/{$missing}/revoke", ['reason' => 'Matrix revoke.']],
        'verifications farm reopen known' => ['POST', "/api/v1/admin/verifications/farms/{$ids['reopenFarm']}/reopen", []],
        'verifications farm reopen missing' => ['POST', "/api/v1/admin/verifications/farms/{$missing}/reopen", []],
    ];
}

function aemMatrixIds(): array
{
    $member = User::factory()->farmer()->create();
    $suspendTarget = User::factory()->buyer()->create();
    $unsuspendTarget = User::factory()->buyer()->create();
    $unsuspendTarget->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Matrix suspension.'])->save();

    $read = aemMessage();
    $close = aemMessage();
    $reopen = aemMessage(['status' => ContactStatus::CLOSED]);
    $replyTo = aemMessage();
    $retryMessage = aemMessage();
    $retry = aemFailedReply($retryMessage);

    $thread = aemThread($member);
    $hide = aemThread($member);
    $restore = aemThread($member);
    $restore->forceFill(['hidden_at' => now(), 'hidden_reason' => 'Matrix hidden.'])->save();

    $reporter = User::factory()->buyer()->create();
    $report = aemReport($reporter, $thread);
    $ticket = aemTicket($reporter);

    $showFarm = Farm::factory()->create();
    $showPlot = Plot::factory()->create(['farm_id' => $showFarm->id]);
    $verifyFarm = Farm::factory()->create();
    $rejectPlot = Plot::factory()->create();
    $revokeFarm = Farm::factory()->create(['verification_status' => VerificationStatus::VERIFIED]);
    $reopenFarm = Farm::factory()->create(['verification_status' => VerificationStatus::REJECTED]);

    return [
        'member' => $member->id,
        'suspend' => $suspendTarget->id,
        'unsuspend' => $unsuspendTarget->id,
        'message' => $read->id,
        'read' => $read->id,
        'close' => $close->id,
        'reopen' => $reopen->id,
        'replyTo' => $replyTo->id,
        'retryMessage' => $retryMessage->id,
        'retry' => $retry->id,
        'thread' => $thread->id,
        'hide' => $hide->id,
        'restore' => $restore->id,
        'report' => $report->id,
        'ticket' => $ticket->id,
        'showFarm' => $showFarm->id,
        'showPlot' => $showPlot->id,
        'verifyFarm' => $verifyFarm->id,
        'rejectPlot' => $rejectPlot->id,
        'revokeFarm' => $revokeFarm->id,
        'reopenFarm' => $reopenFarm->id,
    ];
}

it('denies guests with 401 on every admin route', function () {
    foreach (aemMatrix(aemMatrixIds()) as $label => [$method, $uri, $payload]) {
        Auth::forgetGuards();

        $response = $method === 'GET' ? $this->getJson($uri) : $this->postJson($uri, $payload);

        $response->assertUnauthorized();
    }
});

it('denies members with 403 on every admin route for known and missing ids', function () {
    $matrix = aemMatrix(aemMatrixIds());

    foreach (['farmer', 'buyer'] as $role) {
        $member = $role === 'farmer' ? User::factory()->farmer()->create() : User::factory()->buyer()->create();
        $token = aemTokenFor($member);

        foreach ($matrix as $label => [$method, $uri, $payload]) {
            Auth::forgetGuards();

            $response = $method === 'GET'
                ? $this->withToken($token)->getJson($uri)
                : $this->withToken($token)->postJson($uri, $payload);

            $response->assertForbidden();
        }
    }
});

it('denies unverified admins with 403 on every admin route', function () {
    $admin = User::factory()->unverified()->create(['role' => 'admin']);
    $token = aemTokenFor($admin);

    foreach (aemMatrix(aemMatrixIds()) as $label => [$method, $uri, $payload]) {
        Auth::forgetGuards();

        $response = $method === 'GET'
            ? $this->withToken($token)->getJson($uri)
            : $this->withToken($token)->postJson($uri, $payload);

        $response->assertForbidden();
    }
});

it('serves verified admins and returns 404 for missing targets on every admin route', function () {
    Bus::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $token = aemTokenFor($admin);
    $matrix = aemMatrix(aemMatrixIds());

    $expected = [
        'overview' => 200,
        'users index' => 200,
        'users show known' => 200,
        'users show missing' => 404,
        'users suspend known' => 200,
        'users suspend missing' => 404,
        'users unsuspend known' => 200,
        'users unsuspend missing' => 404,
        'inquiries index' => 200,
        'inquiries show known' => 200,
        'inquiries show missing' => 404,
        'inquiries read known' => 200,
        'inquiries read missing' => 404,
        'inquiries close known' => 200,
        'inquiries close missing' => 404,
        'inquiries reopen known' => 200,
        'inquiries reopen missing' => 404,
        'inquiries replies known' => 202,
        'inquiries replies missing' => 404,
        'inquiries retry known' => 202,
        'inquiries retry missing' => 404,
        'reports index' => 200,
        'reports show known' => 200,
        'reports show missing' => 404,
        'reports decision known' => 200,
        'reports decision missing' => 404,
        'issues index' => 200,
        'issues show known' => 200,
        'issues show missing' => 404,
        'issues transition known' => 200,
        'issues transition missing' => 404,
        'content thread index' => 200,
        'content reply index' => 200,
        'content contract index' => 200,
        'content listing index' => 200,
        'content demand index' => 200,
        'content show known' => 200,
        'content show missing' => 404,
        'content hide known' => 200,
        'content hide missing' => 404,
        'content restore known' => 200,
        'content restore missing' => 404,
        'verifications index' => 200,
        'verifications farm show known' => 200,
        'verifications farm show missing' => 404,
        'verifications plot show known' => 200,
        'verifications plot show missing' => 404,
        'verifications farm verify known' => 200,
        'verifications farm verify missing' => 404,
        'verifications plot reject known' => 200,
        'verifications plot reject missing' => 404,
        'verifications farm revoke known' => 200,
        'verifications farm revoke missing' => 404,
        'verifications farm reopen known' => 200,
        'verifications farm reopen missing' => 404,
    ];

    expect(array_keys($matrix))->toBe(array_keys($expected));

    foreach ($matrix as $label => [$method, $uri, $payload]) {
        Auth::forgetGuards();

        $response = $method === 'GET'
            ? $this->withToken($token)->getJson($uri)
            : $this->withToken($token)->postJson($uri, $payload);

        expect($response->status())->toBe($expected[$label], "matrix entry '{$label}' ({$method} {$uri})");
    }
});

it('returns 404 for wrong-parent and missing nested replies', function () {
    Bus::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $token = aemTokenFor($admin);

    $home = aemMessage();
    $reply = aemFailedReply($home);
    $other = aemMessage();

    Auth::forgetGuards();
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$other->id}/replies/{$reply->id}/retry")
        ->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$home->id}/replies/999999/retry")
        ->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/999999/replies/{$reply->id}/retry")
        ->assertNotFound();

    expect($reply->fresh()->delivery_status)->toBe(ReplyDeliveryStatus::FAILED);
});

it('rejects unlisted content types with 404', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $token = aemTokenFor($admin);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/content/user')->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/content/user/1')->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/admin/content/user/1/hide', ['reason' => 'probe'])->assertNotFound();
});

it('ignores injected ownership and workflow fields on member report creation', function () {
    $owner = User::factory()->farmer()->create();
    $reporter = User::factory()->buyer()->create();
    $other = User::factory()->buyer()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $reporterToken = aemTokenFor($reporter);

    $thread = aemThread($owner);
    $legacyThread = aemThread($owner);

    $injected = [
        'user_id' => $other->id,
        'reporter_id' => $other->id,
        'role' => 'admin',
        'status' => ContentReportStatus::RESOLVED->value,
        'outcome' => 'hidden',
        'version' => 99,
        'reviewed_by' => $admin->id,
        'reviewed_at' => '2026-01-01 00:00:00',
        'resolution_note' => 'Forged decision.',
        'target_snapshot' => ['type' => 'user', 'id' => $other->id, 'forged' => true],
    ];

    Auth::forgetGuards();
    $this->withToken($reporterToken)->postJson('/api/v1/reports', array_merge([
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $thread->id,
        'reason' => ContentReportReason::SUSPECTED_FRAUD->value,
    ], $injected))->assertCreated();

    $stored = ContentReport::where('reportable_id', $thread->id)
        ->where('reportable_type', ReportTargetType::THREAD->value)
        ->firstOrFail();

    expect($stored->user_id)->toBe($reporter->id)
        ->and($stored->status)->toBe(ContentReportStatus::OPEN)
        ->and($stored->version)->toBe(1)
        ->and($stored->reviewed_by)->toBeNull()
        ->and($stored->reviewed_at)->toBeNull()
        ->and($stored->outcome)->toBeNull()
        ->and($stored->resolution_note)->toBeNull()
        ->and($stored->target_snapshot['type'] ?? null)->toBe(ReportTargetType::THREAD->value)
        ->and($stored->target_snapshot['forged'] ?? null)->toBeNull();

    Auth::forgetGuards();
    $this->withToken($reporterToken)->postJson('/api/v1/forum/reports', array_merge([
        'reportable_type' => ReportTargetType::THREAD->value,
        'reportable_id' => $legacyThread->id,
        'reason' => ContentReportReason::SPAM->value,
    ], $injected))->assertOk();

    $legacy = ContentReport::where('reportable_id', $legacyThread->id)
        ->where('reportable_type', ReportTargetType::THREAD->value)
        ->firstOrFail();

    expect($legacy->user_id)->toBe($reporter->id)
        ->and($legacy->status)->toBe(ContentReportStatus::OPEN)
        ->and($legacy->version)->toBe(1)
        ->and($legacy->reviewed_by)->toBeNull()
        ->and($legacy->outcome)->toBeNull()
        ->and($legacy->target_snapshot['forged'] ?? null)->toBeNull();
});
