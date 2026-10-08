<?php

use App\Domain\Contact\Actions\TransitionIssueTicketAction;
use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function adminIssueAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function adminIssueTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('admin-issue-test-token')->plainTextToken;
}

function adminIssueReporter(string $role = 'buyer'): User
{
    return $role === 'farmer' ? User::factory()->farmer()->create() : User::factory()->buyer()->create();
}

function adminIssueTicket(User $reporter, array $overrides = []): IssueTicket
{
    return IssueTicket::create(array_merge([
        'user_id' => $reporter->id,
        'category' => IssueCategory::TECHNICAL->value,
        'subject' => 'Checkout button does nothing',
        'description' => 'Tapping checkout never advances.',
        'page_path' => '/dashboard/marketplace',
        'status' => IssueStatus::OPEN->value,
        'client_request_id' => (string) Str::uuid(),
    ], $overrides));
}

function adminIssueTransition(array $overrides = []): array
{
    return array_merge([
        'status' => IssueStatus::IN_PROGRESS->value,
        'expected_version' => 1,
    ], $overrides);
}

it('fences every admin issue endpoint before resource binding', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();
    $ticket = adminIssueTicket($reporter);

    $this->getJson('/api/v1/admin/issues')->assertUnauthorized();
    $this->getJson("/api/v1/admin/issues/{$ticket->id}")->assertUnauthorized();

    foreach (['farmer', 'buyer'] as $role) {
        $token = adminIssueTokenFor(adminIssueReporter($role));

        $this->withToken($token)->getJson('/api/v1/admin/issues')->assertForbidden();
        $this->withToken($token)->getJson("/api/v1/admin/issues/{$ticket->id}")->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/admin/issues/999999')->assertForbidden();
        $this->withToken($token)
            ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition())
            ->assertForbidden();
        $this->withToken($token)
            ->postJson('/api/v1/admin/issues/999999/transition', adminIssueTransition())
            ->assertForbidden();
    }

    $unverified = User::factory()->unverified()->create(['role' => 'admin']);
    $this->withToken(adminIssueTokenFor($unverified))->getJson('/api/v1/admin/issues')->assertForbidden();

    expect($admin->id)->not->toBe($reporter->id);
});

it('lists every ticket for a verified admin', function () {
    $admin = adminIssueAdmin();
    $buyer = adminIssueReporter('buyer');
    $farmer = adminIssueReporter('farmer');

    adminIssueTicket($buyer, ['subject' => 'Buyer ticket']);
    adminIssueTicket($farmer, ['subject' => 'Farmer ticket']);

    $response = $this->withToken(adminIssueTokenFor($admin))->getJson('/api/v1/admin/issues');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
    expect($response->json('data.0.id'))->toBeString();
});

it('filters the admin list by status and category', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();

    adminIssueTicket($reporter, ['subject' => 'Technical open', 'category' => IssueCategory::TECHNICAL->value]);
    adminIssueTicket($reporter, [
        'subject' => 'Payment open',
        'category' => IssueCategory::PAYMENT->value,
        'status' => IssueStatus::IN_PROGRESS->value,
    ]);

    $token = adminIssueTokenFor($admin);

    $status = $this->withToken($token)->getJson('/api/v1/admin/issues?status=in_progress');
    $status->assertOk();
    expect($status->json('meta.total'))->toBe(1);
    expect($status->json('data.0.subject'))->toBe('Payment open');

    $category = $this->withToken($token)->getJson('/api/v1/admin/issues?category=payment');
    $category->assertOk();
    expect($category->json('meta.total'))->toBe(1);

    $this->withToken($token)->getJson('/api/v1/admin/issues?status=archived')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/issues?category=refund')->assertUnprocessable();
});

it('searches subjects and descriptions as a literal substring', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();

    adminIssueTicket($reporter, ['subject' => '100% harvest bonus missing']);
    adminIssueTicket($reporter, ['subject' => 'Login loop', 'description' => 'Cannot finish the harvest listing form.']);

    $token = adminIssueTokenFor($admin);

    $subject = $this->withToken($token)->getJson('/api/v1/admin/issues?search=100%25');
    $subject->assertOk();
    expect($subject->json('meta.total'))->toBe(1);
    expect($subject->json('data.0.subject'))->toBe('100% harvest bonus missing');

    $description = $this->withToken($token)->getJson('/api/v1/admin/issues?search=listing form');
    $description->assertOk();
    expect($description->json('meta.total'))->toBe(1);

    $this->withToken($token)->getJson('/api/v1/admin/issues?search='.str_repeat('s', 101))->assertUnprocessable();
});

it('rejects invalid admin pagination bounds', function () {
    $admin = adminIssueAdmin();

    $token = adminIssueTokenFor($admin);

    $this->withToken($token)->getJson('/api/v1/admin/issues?page=0')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/issues?page=10001')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/issues?per_page=0')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/admin/issues?per_page=101')->assertUnprocessable();
});

it('shows one ticket with reporter identity and version', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();
    $ticket = adminIssueTicket($reporter);

    $response = $this->withToken(adminIssueTokenFor($admin))->getJson("/api/v1/admin/issues/{$ticket->id}");

    $response->assertOk();
    expect($response->json('data.id'))->toBe((string) $ticket->id);
    expect($response->json('data.user_id'))->toBe((string) $reporter->id);
    expect($response->json('data.version'))->toBe(1);
    expect($response->json('data.reporter.id'))->toBe((string) $reporter->id);
    expect($response->json('data.reporter.email'))->toBe($reporter->email);
});

it('returns 404 for a missing admin ticket', function () {
    $admin = adminIssueAdmin();

    $this->withToken(adminIssueTokenFor($admin))->getJson('/api/v1/admin/issues/999999')->assertNotFound();
    $this->withToken(adminIssueTokenFor($admin))
        ->postJson('/api/v1/admin/issues/999999/transition', adminIssueTransition())
        ->assertNotFound();
});

it('moves an open ticket to in progress and records history', function () {
    $admin = adminIssueAdmin();
    $ticket = adminIssueTicket(adminIssueReporter());

    $response = $this->withToken(adminIssueTokenFor($admin))
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition());

    $response->assertOk();
    expect($response->json('data.status'))->toBe(IssueStatus::IN_PROGRESS->value);
    expect($response->json('data.version'))->toBe(2);

    $history = AdminActionLog::where('action', AdminAction::ISSUE_TRANSITIONED->value)->sole();
    expect($history->actor_id)->toBe($admin->id);
    expect($history->subject_type)->toBe('issue_ticket');
    expect($history->subject_id)->toBe((string) $ticket->id);
    expect($history->before['status'])->toBe(IssueStatus::OPEN->value);
    expect($history->before['version'])->toBe(1);
    expect($history->after['status'])->toBe(IssueStatus::IN_PROGRESS->value);
    expect($history->after['version'])->toBe(2);
});

it('requires the expected version and echoes the winner on conflict', function () {
    $admin = adminIssueAdmin();
    $ticket = adminIssueTicket(adminIssueReporter());

    $token = adminIssueTokenFor($admin);

    $missing = adminIssueTransition();
    unset($missing['expected_version']);

    $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", $missing)
        ->assertUnprocessable();

    $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition())
        ->assertOk();

    $stale = $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition(['expected_version' => 1]));

    $stale->assertConflict();
    expect($stale->json('current_version'))->toBe(2);
    expect($ticket->fresh()->version)->toBe(2);
});

it('rejects repeated and invalid transitions with 409', function () {
    $admin = adminIssueAdmin();
    $ticket = adminIssueTicket(adminIssueReporter());

    $token = adminIssueTokenFor($admin);

    $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition(['status' => IssueStatus::OPEN->value]))
        ->assertConflict();

    $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition())
        ->assertOk();

    $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition(['expected_version' => 2]))
        ->assertConflict();

    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/issues/{$ticket->id}/transition",
            adminIssueTransition(['status' => IssueStatus::OPEN->value, 'expected_version' => 2])
        )
        ->assertConflict();

    expect($ticket->fresh()->status)->toBe(IssueStatus::IN_PROGRESS);
    expect($ticket->fresh()->version)->toBe(2);
});

it('requires a bounded resolution when resolving', function (mixed $resolution, bool $valid) {
    $admin = adminIssueAdmin();
    $ticket = adminIssueTicket(adminIssueReporter());

    $payload = adminIssueTransition(['status' => IssueStatus::RESOLVED->value]);
    $payload['resolution'] = $resolution;

    $response = $this->withToken(adminIssueTokenFor($admin))
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", $payload);

    $valid ? $response->assertOk() : $response->assertUnprocessable();
})->with([
    'one char' => ['x', true],
    'max length' => [str_repeat('r', 2000), true],
    'too long' => [str_repeat('r', 2001), false],
    'empty' => ['', false],
    'whitespace only' => ['   ', false],
]);

it('requires a resolution when closing and forbids it when reopening', function () {
    $admin = adminIssueAdmin();
    $ticket = adminIssueTicket(adminIssueReporter());

    $token = adminIssueTokenFor($admin);

    $this->withToken($token)
        ->postJson("/api/v1/admin/issues/{$ticket->id}/transition", adminIssueTransition(['status' => IssueStatus::CLOSED->value]))
        ->assertUnprocessable();

    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/issues/{$ticket->id}/transition",
            adminIssueTransition(['resolution' => 'Not needed yet.'])
        )
        ->assertUnprocessable();

    $close = $this->withToken($token)
        ->postJson(
            "/api/v1/admin/issues/{$ticket->id}/transition",
            adminIssueTransition(['status' => IssueStatus::CLOSED->value, 'resolution' => 'Duplicate of an earlier ticket.'])
        );

    $close->assertOk();
    expect($close->json('data.resolution'))->toBe('Duplicate of an earlier ticket.');
    expect($close->json('data.resolved_by'))->toBe((string) $admin->id);
    expect($ticket->fresh()->resolved_at)->not->toBeNull();
});

it('supports the full documented transition matrix', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();

    $direct = adminIssueTicket($reporter);
    app(TransitionIssueTicketAction::class)->execute($admin, $direct, IssueStatus::RESOLVED, 'Fixed directly.', 1);
    expect($direct->fresh()->status)->toBe(IssueStatus::RESOLVED);

    $viaProgress = adminIssueTicket($reporter);
    app(TransitionIssueTicketAction::class)->execute($admin, $viaProgress, IssueStatus::IN_PROGRESS, null, 1);
    app(TransitionIssueTicketAction::class)->execute($admin, $viaProgress->fresh(), IssueStatus::RESOLVED, 'Fixed.', 2);
    expect($viaProgress->fresh()->status)->toBe(IssueStatus::RESOLVED);

    $resolvedToClosed = adminIssueTicket($reporter);
    app(TransitionIssueTicketAction::class)->execute($admin, $resolvedToClosed, IssueStatus::CLOSED, 'Closing.', 1);
    expect($resolvedToClosed->fresh()->status)->toBe(IssueStatus::CLOSED);

    $closedToProgress = adminIssueTicket($reporter);
    app(TransitionIssueTicketAction::class)->execute($admin, $closedToProgress, IssueStatus::CLOSED, 'Closing.', 1);
    app(TransitionIssueTicketAction::class)->execute($admin, $closedToProgress->fresh(), IssueStatus::IN_PROGRESS, null, 2);
    expect($closedToProgress->fresh()->status)->toBe(IssueStatus::IN_PROGRESS);
});

it('clears resolution fields on reopen while keeping history', function () {
    $admin = adminIssueAdmin();
    $ticket = adminIssueTicket(adminIssueReporter());

    $token = adminIssueTokenFor($admin);

    $this->withToken($token)
        ->postJson(
            "/api/v1/admin/issues/{$ticket->id}/transition",
            adminIssueTransition(['status' => IssueStatus::RESOLVED->value, 'resolution' => 'Fixed.'])
        )
        ->assertOk();

    $reopen = $this->withToken($token)
        ->postJson(
            "/api/v1/admin/issues/{$ticket->id}/transition",
            adminIssueTransition(['expected_version' => 2])
        );

    $reopen->assertOk();
    expect($reopen->json('data.status'))->toBe(IssueStatus::IN_PROGRESS->value);
    expect($reopen->json('data.version'))->toBe(3);
    expect($reopen->json('data.resolution'))->toBeNull();
    expect($reopen->json('data.resolved_by'))->toBeNull();

    $reopened = $ticket->fresh();
    expect($reopened->resolution)->toBeNull();
    expect($reopened->resolved_by)->toBeNull();
    expect($reopened->resolved_at)->toBeNull();

    expect(AdminActionLog::where('action', AdminAction::ISSUE_TRANSITIONED->value)->count())->toBe(2);
});

it('ignores injected ownership fields on transition', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();
    $other = adminIssueReporter('farmer');
    $ticket = adminIssueTicket($reporter);

    $response = $this->withToken(adminIssueTokenFor($admin))
        ->postJson(
            "/api/v1/admin/issues/{$ticket->id}/transition",
            adminIssueTransition(['user_id' => $other->id, 'version' => 99])
        );

    $response->assertOk();
    expect($ticket->fresh()->user_id)->toBe($reporter->id);
    expect($ticket->fresh()->version)->toBe(2);
});

it('renders a deleted reporter as unavailable without private identity', function () {
    $admin = adminIssueAdmin();
    $reporter = adminIssueReporter();
    $email = $reporter->email;
    $ticket = adminIssueTicket($reporter);

    $reporter->delete();

    $token = adminIssueTokenFor($admin);

    $show = $this->withToken($token)->getJson("/api/v1/admin/issues/{$ticket->id}");
    $show->assertOk();
    expect($show->json('data.user_id'))->toBeNull();
    expect($show->json('data.reporter'))->toBeNull();
    expect(json_encode($show->json()))->not->toContain($email);

    $index = $this->withToken($token)->getJson('/api/v1/admin/issues');
    $index->assertOk();
    expect($index->json('meta.total'))->toBe(1);
});
