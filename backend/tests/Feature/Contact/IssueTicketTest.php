<?php

use App\Domain\Contact\Actions\CreateIssueTicketAction;
use App\Domain\Contact\Actions\TransitionIssueTicketAction;
use App\Domain\Contact\Enums\IssueCategory;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function issuePayload(array $overrides = []): array
{
    return array_merge([
        'category' => IssueCategory::TECHNICAL->value,
        'subject' => 'Checkout button does nothing',
        'description' => 'Tapping checkout on my harvest listing never advances past the first step.',
        'page_path' => '/dashboard/marketplace',
        'client_request_id' => (string) Str::uuid(),
    ], $overrides);
}

function issueReporter(string $role = 'buyer'): User
{
    return $role === 'farmer' ? User::factory()->farmer()->create() : User::factory()->buyer()->create();
}

function issueStoredTicket(User $reporter, array $overrides = []): IssueTicket
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

it('lets a verified buyer create a ticket', function () {
    $reporter = issueReporter('buyer');

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/issues', issuePayload());

    $response->assertCreated();
    expect($response->json('data.id'))->toBeString();
    expect($response->json('data.status'))->toBe(IssueStatus::OPEN->value);
    expect($response->json('data.category'))->toBe(IssueCategory::TECHNICAL->value);
    expect($response->json('data.resolution'))->toBeNull();

    $ticket = IssueTicket::sole();
    expect($ticket->user_id)->toBe($reporter->id);
    expect($ticket->status)->toBe(IssueStatus::OPEN);
    expect($ticket->version)->toBe(1);
});

it('lets a verified farmer create a ticket', function () {
    $reporter = issueReporter('farmer');

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/issues', issuePayload())->assertCreated();

    expect(IssueTicket::sole()->user_id)->toBe($reporter->id);
});

it('rejects guests with 401', function () {
    $this->postJson('/api/v1/issues', issuePayload())->assertUnauthorized();
    $this->getJson('/api/v1/issues')->assertUnauthorized();
    $this->getJson('/api/v1/issues/1')->assertUnauthorized();
});

it('rejects unverified members with 403', function () {
    $reporter = User::factory()->unverified()->buyer()->create();

    Sanctum::actingAs($reporter, ['*']);

    $this->postJson('/api/v1/issues', issuePayload())->assertForbidden();
    $this->getJson('/api/v1/issues')->assertForbidden();
});

it('rejects admin issue creation and member reads with 403', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($admin, ['*']);

    $this->postJson('/api/v1/issues', issuePayload())->assertForbidden();
    $this->getJson('/api/v1/issues')->assertForbidden();
    $this->getJson('/api/v1/issues/1')->assertForbidden();
});

it('shows a reporter their own ticket', function () {
    $reporter = issueReporter();
    $ticket = issueStoredTicket($reporter);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->getJson("/api/v1/issues/{$ticket->id}");

    $response->assertOk();
    expect($response->json('data.id'))->toBe((string) $ticket->id);
    expect($response->json('data.subject'))->toBe('Checkout button does nothing');
    expect($response->json('data.status'))->toBe(IssueStatus::OPEN->value);
});

it('returns 404 for another reporter ticket and missing tickets', function () {
    $reporter = issueReporter();
    $other = issueReporter('farmer');
    $ticket = issueStoredTicket($other);

    Sanctum::actingAs($reporter, ['*']);

    $this->getJson("/api/v1/issues/{$ticket->id}")->assertNotFound();
    $this->getJson('/api/v1/issues/999999')->assertNotFound();
});

it('lists only the authenticated reporter tickets', function () {
    $reporter = issueReporter();
    $other = issueReporter('farmer');

    issueStoredTicket($reporter, ['subject' => 'First own ticket']);
    issueStoredTicket($reporter, ['subject' => 'Second own ticket']);
    issueStoredTicket($other, ['subject' => 'Someone else ticket']);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->getJson('/api/v1/issues');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
    expect(collect($response->json('data'))->pluck('subject')->all())
        ->toBe(['Second own ticket', 'First own ticket']);
});

it('keeps list scope server-side when a reporter id is injected', function () {
    $reporter = issueReporter();
    $other = issueReporter('farmer');

    issueStoredTicket($reporter);
    issueStoredTicket($other);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->getJson("/api/v1/issues?user_id={$other->id}");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1);
});

it('ignores injected ownership and workflow fields on create', function () {
    $reporter = issueReporter();
    $other = issueReporter('farmer');
    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->postJson('/api/v1/issues', issuePayload([
        'user_id' => $other->id,
        'status' => IssueStatus::RESOLVED->value,
        'resolved_by' => $admin->id,
        'resolution' => 'Pretend this is fixed.',
    ]));

    $response->assertCreated();

    $ticket = IssueTicket::sole();
    expect($ticket->user_id)->toBe($reporter->id);
    expect($ticket->status)->toBe(IssueStatus::OPEN);
    expect($ticket->resolved_by)->toBeNull();
    expect($ticket->resolution)->toBeNull();
});

it('validates the subject bounds', function (mixed $subject, bool $valid) {
    $reporter = issueReporter();

    Sanctum::actingAs($reporter, ['*']);

    $payload = issuePayload();
    $payload['subject'] = $subject;

    $response = $this->postJson('/api/v1/issues', $payload);

    $valid ? $response->assertCreated() : $response->assertUnprocessable();
})->with([
    'one char' => ['x', true],
    'max length' => [str_repeat('s', 150), true],
    'too long' => [str_repeat('s', 151), false],
    'empty' => ['', false],
    'whitespace only' => ['   ', false],
]);

it('rejects a missing subject', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $payload = issuePayload();
    unset($payload['subject']);

    $this->postJson('/api/v1/issues', $payload)->assertUnprocessable();
});

it('validates the description bounds', function (mixed $description, bool $valid) {
    $reporter = issueReporter();

    Sanctum::actingAs($reporter, ['*']);

    $payload = issuePayload();
    $payload['description'] = $description;

    $response = $this->postJson('/api/v1/issues', $payload);

    $valid ? $response->assertCreated() : $response->assertUnprocessable();
})->with([
    'one char' => ['x', true],
    'max length' => [str_repeat('d', 5000), true],
    'too long' => [str_repeat('d', 5001), false],
    'empty' => ['', false],
    'whitespace only' => ["  \t ", false],
]);

it('rejects a missing description', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $payload = issuePayload();
    unset($payload['description']);

    $this->postJson('/api/v1/issues', $payload)->assertUnprocessable();
});

it('accepts every documented category', function (string $category) {
    Sanctum::actingAs(issueReporter(), ['*']);

    $response = $this->postJson('/api/v1/issues', issuePayload(['category' => $category]));

    $response->assertCreated();
    expect($response->json('data.category'))->toBe($category);
})->with([
    'technical' => ['technical'],
    'account' => ['account'],
    'marketplace' => ['marketplace'],
    'payment' => ['payment'],
    'other' => ['other'],
]);

it('rejects unknown categories', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $this->postJson('/api/v1/issues', issuePayload(['category' => 'refund']))->assertUnprocessable();
});

it('validates the page path', function (mixed $pagePath, bool $valid) {
    Sanctum::actingAs(issueReporter(), ['*']);

    $response = $this->postJson('/api/v1/issues', issuePayload(['page_path' => $pagePath]));

    $valid ? $response->assertCreated() : $response->assertUnprocessable();
})->with([
    'relative path' => ['/dashboard/issues', true],
    'root path' => ['/', true],
    'nested path' => ['/dashboard/marketplace/contracts/25', true],
    'external url' => ['https://evil.example.com/issues', false],
    'protocol relative' => ['//evil.example.com/issues', false],
    'embedded double slash' => ['/dashboard//issues', false],
    'query token' => ['/dashboard/issues?token=secret', false],
    'fragment' => ['/dashboard/issues#section', false],
    'control characters' => ["/dashboard/iss\nues", false],
    'max length' => ['/'.str_repeat('p', 254), true],
    'too long' => ['/'.str_repeat('p', 255), false],
]);

it('stores a missing or empty page path as null', function () {
    $reporter = issueReporter();

    Sanctum::actingAs($reporter, ['*']);

    $missing = issuePayload();
    unset($missing['page_path']);

    $this->postJson('/api/v1/issues', $missing)->assertCreated();
    $this->postJson('/api/v1/issues', issuePayload(['page_path' => '']))->assertCreated();

    expect(IssueTicket::where('user_id', $reporter->id)->whereNull('page_path')->count())->toBe(2);
});

it('requires a uuid request key', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $missing = issuePayload();
    unset($missing['client_request_id']);

    $this->postJson('/api/v1/issues', $missing)->assertUnprocessable();
    $this->postJson('/api/v1/issues', issuePayload(['client_request_id' => 'not-a-uuid']))->assertUnprocessable();
});

it('replays an identical request key without duplicating the ticket', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $payload = issuePayload();

    $first = $this->postJson('/api/v1/issues', $payload);
    $first->assertCreated();

    $second = $this->postJson('/api/v1/issues', $payload);
    $second->assertOk();
    expect($second->json('data.id'))->toBe($first->json('data.id'));

    expect(IssueTicket::count())->toBe(1);
});

it('rejects a reused request key with a changed payload', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $payload = issuePayload();

    $this->postJson('/api/v1/issues', $payload)->assertCreated();

    $changed = $payload;
    $changed['subject'] = 'A different subject';

    $this->postJson('/api/v1/issues', $changed)->assertConflict();

    expect(IssueTicket::count())->toBe(1);
});

it('creates a single row for simultaneous creates with one key', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the parallel submission test.');
    }

    $reporter = issueReporter();
    $reporterId = $reporter->id;
    $requestId = (string) Str::uuid();

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
                    app(CreateIssueTicketAction::class)->execute($childReporter, [
                        'category' => IssueCategory::TECHNICAL->value,
                        'subject' => 'Race subject',
                        'description' => 'Race description.',
                        'page_path' => '/dashboard/issues',
                        'client_request_id' => $requestId,
                    ]);
                    exit(0);
                } catch (Throwable $e) {
                    fwrite(STDERR, 'parallel issue failed: '.$e->getMessage());
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
        expect(DB::table('issue_tickets')->where('user_id', $reporterId)->count())->toBe(1);
    } finally {
        DB::table('issue_tickets')->where('user_id', $reporterId)->delete();
        DB::table('users')->where('id', $reporterId)->delete();
        DB::purge();

        if (DB::transactionLevel() === 0) {
            DB::beginTransaction();
        }
    }
});

it('limits issue creation to five requests per minute', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $payload = issuePayload();

    $this->postJson('/api/v1/issues', $payload)->assertCreated();

    for ($i = 0; $i < 4; $i++) {
        $this->postJson('/api/v1/issues', $payload)->assertOk();
    }

    $this->postJson('/api/v1/issues', $payload)->assertStatus(429);
});

it('limits issue creation to twenty requests per day', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $payload = issuePayload();

    try {
        for ($batch = 0; $batch < 4; $batch++) {
            for ($i = 0; $i < 5; $i++) {
                $response = $this->postJson('/api/v1/issues', $payload);

                if ($batch === 0 && $i === 0) {
                    $response->assertCreated();
                } else {
                    $response->assertOk();
                }
            }

            $this->travel(61)->seconds();
        }

        $this->postJson('/api/v1/issues', $payload)->assertStatus(429);
    } finally {
        $this->travelBack();
    }
});

it('filters the own list by status', function () {
    $reporter = issueReporter();
    $admin = User::factory()->create(['role' => 'admin']);

    $open = issueStoredTicket($reporter, ['subject' => 'Still open']);
    $closed = issueStoredTicket($reporter, ['subject' => 'Already closed']);

    Auth::forgetGuards();
    app(TransitionIssueTicketAction::class)->execute(
        $admin,
        $closed,
        IssueStatus::CLOSED,
        'Fixed in the latest release.',
        1
    );

    Sanctum::actingAs($reporter, ['*']);

    $openResponse = $this->getJson('/api/v1/issues?status=open');
    $openResponse->assertOk();
    expect($openResponse->json('meta.total'))->toBe(1);
    expect($openResponse->json('data.0.subject'))->toBe('Still open');

    $closedResponse = $this->getJson('/api/v1/issues?status=closed');
    $closedResponse->assertOk();
    expect($closedResponse->json('meta.total'))->toBe(1);
    expect($closedResponse->json('data.0.resolution'))->toBe('Fixed in the latest release.');
    expect($open->id)->not->toBe($closed->id);
});

it('rejects invalid status filters and pagination bounds', function () {
    Sanctum::actingAs(issueReporter(), ['*']);

    $this->getJson('/api/v1/issues?status=archived')->assertUnprocessable();
    $this->getJson('/api/v1/issues?page=0')->assertUnprocessable();
    $this->getJson('/api/v1/issues?page=10001')->assertUnprocessable();
    $this->getJson('/api/v1/issues?per_page=0')->assertUnprocessable();
    $this->getJson('/api/v1/issues?per_page=101')->assertUnprocessable();
    $this->getJson('/api/v1/issues?page=1&per_page=100')->assertOk();
});

it('shows the current status and resolution without internal fields', function () {
    $reporter = issueReporter();
    $admin = User::factory()->create(['role' => 'admin']);
    $ticket = issueStoredTicket($reporter);

    app(TransitionIssueTicketAction::class)->execute(
        $admin,
        $ticket,
        IssueStatus::RESOLVED,
        'Cleared the stuck checkout flag.',
        1
    );

    Sanctum::actingAs($reporter, ['*']);

    $response = $this->getJson("/api/v1/issues/{$ticket->id}");

    $response->assertOk();
    expect($response->json('data.status'))->toBe(IssueStatus::RESOLVED->value);
    expect($response->json('data.resolution'))->toBe('Cleared the stuck checkout flag.');
    expect($response->json('data'))->not->toHaveKeys(['resolved_by', 'user_id', 'client_request_id']);
});

it('retains the ticket when the reporter is deleted', function () {
    $reporter = issueReporter();
    $ticket = issueStoredTicket($reporter);

    $reporter->delete();

    $retained = IssueTicket::find($ticket->id);

    expect($retained)->not->toBeNull();
    expect($retained->user_id)->toBeNull();
    expect($retained->subject)->toBe('Checkout button does nothing');
});
