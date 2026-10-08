<?php

use App\Constants\ContactConstants;
use App\Domain\Contact\Actions\RetryContactReplyAction;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;
use App\Infrastructure\Services\ContactReplyMailService;
use App\Jobs\SendContactReplyJob;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function contactReplyAdminToken(): string
{
    Auth::forgetGuards();

    $admin = User::factory()->create(['role' => 'admin']);

    return $admin->createToken('contact-reply-test-token')->plainTextToken;
}

function contactReplyMessage(array $attributes = []): ContactMessage
{
    return ContactMessage::create(array_merge([
        'name' => 'Ramon Reply',
        'email' => 'ramon@example.com',
        'subject' => 'Delivery question',
        'message' => 'Where is my order?',
    ], $attributes));
}

function contactReplyRow(ContactMessage $message, array $attributes = []): ContactMessageReply
{
    return app(ContactMessageReplyRepositoryInterface::class)->create(array_merge([
        'message_id' => $message->id,
        'admin_id' => null,
        'recipient' => $message->email,
        'body' => 'Thanks for writing to us.',
        'client_request_id' => (string) Str::uuid(),
        'delivery_status' => ReplyDeliveryStatus::QUEUED,
        'attempts' => 0,
        'delivery_generation' => 0,
    ], $attributes));
}

function contactReplyJob(int $replyId): SendContactReplyJob
{
    return new SendContactReplyJob($replyId);
}

function contactReplyHandle(SendContactReplyJob $job): void
{
    $job->handle(app(ContactReplyMailService::class), app(ContactMessageReplyRepositoryInterface::class));
}

it('validates reply body bounds and the request key', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();

    $post = fn (array $payload) => $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", $payload);

    Auth::forgetGuards();
    $post(['body' => '', 'client_request_id' => (string) Str::uuid()])->assertStatus(422);

    Auth::forgetGuards();
    $post(['body' => '   ', 'client_request_id' => (string) Str::uuid()])->assertStatus(422)
        ->assertJsonValidationErrors(['body']);

    Auth::forgetGuards();
    $post(['body' => 'x', 'client_request_id' => (string) Str::uuid()])->assertStatus(202);

    $second = contactReplyMessage();
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$second->id}/replies", [
        'body' => str_repeat('b', ContactConstants::REPLY_BODY_MAX_LENGTH),
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(202);

    $third = contactReplyMessage();
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$third->id}/replies", [
        'body' => str_repeat('b', ContactConstants::REPLY_BODY_MAX_LENGTH + 1),
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(422)->assertJsonValidationErrors(['body']);

    Auth::forgetGuards();
    $post(['body' => 'missing key'])->assertStatus(422)->assertJsonValidationErrors(['client_request_id']);

    Auth::forgetGuards();
    $post(['body' => 'bad key', 'client_request_id' => 'not-a-uuid'])->assertStatus(422)
        ->assertJsonValidationErrors(['client_request_id']);
});

it('sends replies to the original inquiry address, never a submitted recipient', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage(['email' => 'original@example.com']);

    $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Hello from support.',
        'client_request_id' => (string) Str::uuid(),
        'recipient' => 'attacker@example.com',
    ]);

    $response->assertStatus(202)->assertJsonPath('data.recipient', 'original@example.com');
    expect(ContactMessageReply::firstOrFail()->recipient)->toBe('original@example.com');
});

it('queues without claiming sent and dispatches exactly one job', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();

    $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Queued reply body.',
        'client_request_id' => (string) Str::uuid(),
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.delivery_status', ReplyDeliveryStatus::QUEUED->value)
        ->assertJsonPath('data.sent_at', null)
        ->assertJsonPath('data.attempts', 0)
        ->assertJsonPath('data.delivery_generation', 0);

    Bus::assertDispatched(SendContactReplyJob::class, 1);

    expect($message->fresh()->replied_at)->toBeNull()
        ->and($message->fresh()->status)->toBe(ContactStatus::UNREAD)
        ->and(AdminActionLog::where('action', AdminAction::REPLY_QUEUED->value)->count())->toBe(1);
});

it('replays the same key and payload to the same row but rejects changed payloads', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();
    $requestId = (string) Str::uuid();

    $first = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Stable body.',
        'client_request_id' => $requestId,
    ]);

    $first->assertStatus(202);
    $replyId = $first->json('data.id');

    Auth::forgetGuards();
    $replay = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Stable body.',
        'client_request_id' => $requestId,
    ]);

    $replay->assertStatus(202)->assertJsonPath('data.id', $replyId);
    expect(ContactMessageReply::count())->toBe(1);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Changed body.',
        'client_request_id' => $requestId,
    ])->assertStatus(409);

    expect(ContactMessageReply::count())->toBe(1);
});

it('keeps at most one outstanding reply per inquiry', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();

    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'First attempt.',
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(202);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Second attempt.',
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(409);

    expect(ContactMessageReply::where('message_id', $message->id)
        ->whereIn('delivery_status', [ReplyDeliveryStatus::QUEUED->value, ReplyDeliveryStatus::SENDING->value])
        ->count())->toBe(1);
});

it('rejects replies to closed inquiries', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage(['status' => ContactStatus::CLOSED]);

    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Too late.',
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(409);

    expect(ContactMessageReply::count())->toBe(0);
});

it('delivers end to end on the sync queue and marks the inquiry replied', function () {
    Mail::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();

    $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Delivered body.',
        'client_request_id' => (string) Str::uuid(),
    ]);

    $response->assertStatus(202);

    $reply = ContactMessageReply::firstOrFail();
    expect($reply->delivery_status)->toBe(ReplyDeliveryStatus::SENT)
        ->and($reply->sent_at)->not->toBeNull()
        ->and($reply->attempts)->toBe(1)
        ->and($message->fresh()->status)->toBe(ContactStatus::REPLIED)
        ->and($message->fresh()->replied_at)->not->toBeNull();
});

it('marks the reply failed when queue dispatch throws, preserving retry', function () {
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();

    $dispatcher = Mockery::mock(BusDispatcher::class);
    $dispatcher->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('queue unavailable'));
    $this->app->instance(BusDispatcher::class, $dispatcher);

    $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/replies", [
        'body' => 'Dispatch-failure body.',
        'client_request_id' => (string) Str::uuid(),
    ]);

    $response->assertStatus(202)->assertJsonPath('data.delivery_status', ReplyDeliveryStatus::FAILED->value);

    $reply = ContactMessageReply::firstOrFail();
    expect($reply->body)->toBe('Dispatch-failure body.')
        ->and($reply->recipient)->toBe($message->email)
        ->and($reply->error_code)->toBe(ContactConstants::REPLY_ERROR_TRANSPORT_FAILED)
        ->and($message->fresh()->replied_at)->toBeNull();
});

it('returns a retryable transport failure to queued with sanitized error', function () {
    Bus::fake();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message);

    Mail::shouldReceive('raw')->once()
        ->withArgs(function (string $text, Closure $callback) use ($message): bool {
            if ($text !== 'Thanks for writing to us.') {
                return false;
            }

            $mailMessage = new Message(new Email);
            $callback($mailMessage);

            $recipients = array_map(
                fn (Address $address): string => $address->getAddress(),
                $mailMessage->getTo()
            );

            return $recipients === [$message->email]
                && $mailMessage->getSubject() === ContactConstants::REPLY_EMAIL_SUBJECT;
        })
        ->andThrow(new RuntimeException('smtp: SECRET-CREDENTIAL-LEAK 421 boom'));

    try {
        contactReplyHandle(contactReplyJob($reply->id));
        $this->fail('Expected the transport exception to be rethrown.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toContain('421 boom');
    }

    $fresh = $reply->fresh();
    expect($fresh->delivery_status)->toBe(ReplyDeliveryStatus::QUEUED)
        ->and($fresh->attempts)->toBe(1)
        ->and($fresh->delivery_generation)->toBe(1)
        ->and($fresh->sending_started_at)->toBeNull()
        ->and($fresh->sent_at)->toBeNull()
        ->and($fresh->body)->toBe('Thanks for writing to us.')
        ->and($fresh->recipient)->toBe($message->email)
        ->and($fresh->error_code)->toBe(ContactConstants::REPLY_ERROR_TRANSPORT_FAILED)
        ->and($message->fresh()->replied_at)->toBeNull()
        ->and($message->fresh()->status)->toBe(ContactStatus::UNREAD);
});

it('marks terminal failure as failed and keeps the same row retryable', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message, ['attempts' => 3, 'delivery_generation' => 3]);

    (new SendContactReplyJob($reply->id))->failed(new RuntimeException('550 SECRET-TOKEN rejected'));

    $failed = $reply->fresh();
    expect($failed->delivery_status)->toBe(ReplyDeliveryStatus::FAILED)
        ->and($failed->error_code)->toBe(ContactConstants::REPLY_ERROR_TRANSPORT_FAILED)
        ->and($failed->error_code)->not->toContain('SECRET-TOKEN')
        ->and($failed->body)->toBe('Thanks for writing to us.')
        ->and($failed->attempts)->toBe(3)
        ->and($failed->sent_at)->toBeNull()
        ->and($message->fresh()->replied_at)->toBeNull();

    $response = $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$message->id}/replies/{$reply->id}/retry");

    $response->assertStatus(202)
        ->assertJsonPath('data.id', (string) $reply->id)
        ->assertJsonPath('data.delivery_status', ReplyDeliveryStatus::QUEUED->value)
        ->assertJsonPath('data.delivery_generation', 4)
        ->assertJsonPath('data.error_code', null);

    Bus::assertDispatched(SendContactReplyJob::class, 1);
    expect(AdminActionLog::where('action', AdminAction::REPLY_RETRIED->value)->count())->toBe(1);
});

it('recovers stale queued rows through same-row retry without a duplicate warning', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message);
    DB::table('contact_message_replies')->where('id', $reply->id)->update([
        'updated_at' => now()->subMinutes(6),
    ]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$message->id}/replies/{$reply->id}/retry");

    $response->assertStatus(202)
        ->assertJsonPath('data.id', (string) $reply->id)
        ->assertJsonPath('data.delivery_generation', 1)
        ->assertJsonMissingPath('meta');

    expect(ContactMessageReply::count())->toBe(1);
});

it('recovers stale sending rows with a duplicate-delivery warning', function () {
    Bus::fake();
    $token = contactReplyAdminToken();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message, [
        'delivery_status' => ReplyDeliveryStatus::SENDING,
        'delivery_generation' => 2,
        'attempts' => 2,
        'sending_started_at' => now()->subMinutes(6),
    ]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$message->id}/replies/{$reply->id}/retry");

    $response->assertStatus(202)
        ->assertJsonPath('data.id', (string) $reply->id)
        ->assertJsonPath('data.delivery_status', ReplyDeliveryStatus::QUEUED->value)
        ->assertJsonPath('data.delivery_generation', 3)
        ->assertJsonPath('meta.duplicate_delivery_possible', true);

    expect(ContactMessageReply::count())->toBe(1);
});

it('refuses retry while a live job owns the row or after send', function () {
    Bus::fake();
    $token = contactReplyAdminToken();

    $queued = contactReplyMessage();
    $queuedReply = contactReplyRow($queued);
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$queued->id}/replies/{$queuedReply->id}/retry")
        ->assertStatus(409);

    $sending = contactReplyMessage();
    $sendingReply = contactReplyRow($sending, [
        'delivery_status' => ReplyDeliveryStatus::SENDING,
        'delivery_generation' => 1,
        'sending_started_at' => now()->subMinute(),
    ]);
    Auth::forgetGuards();
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$sending->id}/replies/{$sendingReply->id}/retry")
        ->assertStatus(409);

    $sent = contactReplyMessage();
    $sentReply = contactReplyRow($sent, [
        'delivery_status' => ReplyDeliveryStatus::SENT,
        'delivery_generation' => 1,
        'sent_at' => now(),
    ]);
    Auth::forgetGuards();
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$sent->id}/replies/{$sentReply->id}/retry")
        ->assertStatus(409);
});

it('ignores late completion from an invalidated generation', function () {
    Bus::fake();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message);

    Mail::shouldReceive('raw')->once()->andReturnUsing(function () use ($reply): void {
        DB::table('contact_message_replies')->where('id', $reply->id)->update([
            'delivery_status' => ReplyDeliveryStatus::QUEUED->value,
            'delivery_generation' => 2,
            'sending_started_at' => null,
        ]);
    });

    contactReplyHandle(contactReplyJob($reply->id));

    $fresh = $reply->fresh();
    expect($fresh->delivery_status)->toBe(ReplyDeliveryStatus::QUEUED)
        ->and($fresh->delivery_generation)->toBe(2)
        ->and($fresh->sent_at)->toBeNull()
        ->and($message->fresh()->status)->not->toBe(ContactStatus::REPLIED)
        ->and($message->fresh()->replied_at)->toBeNull();
});

it('skips duplicate jobs once the reply is sent', function () {
    Bus::fake();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message);

    Mail::shouldReceive('raw')->once()->andReturnNull();

    contactReplyHandle(contactReplyJob($reply->id));
    contactReplyHandle(contactReplyJob($reply->id));

    $fresh = $reply->fresh();
    expect($fresh->delivery_status)->toBe(ReplyDeliveryStatus::SENT)
        ->and($fresh->attempts)->toBe(1)
        ->and($message->fresh()->status)->toBe(ContactStatus::REPLIED);
});

it('never calls transport for an already-sent reply', function () {
    Bus::fake();
    $message = contactReplyMessage(['status' => ContactStatus::REPLIED, 'replied_at' => now()]);
    $reply = contactReplyRow($message, [
        'delivery_status' => ReplyDeliveryStatus::SENT,
        'delivery_generation' => 1,
        'attempts' => 1,
        'sent_at' => now(),
    ]);

    Mail::shouldReceive('raw')->never();

    contactReplyHandle(contactReplyJob($reply->id));

    expect($reply->fresh()->delivery_status)->toBe(ReplyDeliveryStatus::SENT)
        ->and($reply->fresh()->attempts)->toBe(1);
});

it('orders close against queue consistently in both directions', function () {
    Bus::fake();
    $token = contactReplyAdminToken();

    $outstanding = contactReplyMessage();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$outstanding->id}/replies", [
        'body' => 'Outstanding reply.',
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(202);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$outstanding->id}/close")->assertStatus(409);

    $closed = contactReplyMessage();
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$closed->id}/close")->assertOk();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$closed->id}/replies", [
        'body' => 'Late reply.',
        'client_request_id' => (string) Str::uuid(),
    ])->assertStatus(409);
});

it('never overwrites a closed inquiry when a recovered reply completes', function () {
    Bus::fake();
    $message = contactReplyMessage();
    $reply = contactReplyRow($message, ['delivery_status' => ReplyDeliveryStatus::FAILED]);

    $message->forceFill(['status' => ContactStatus::CLOSED])->save();

    Mail::shouldReceive('raw')->once()->andReturnNull();

    app(RetryContactReplyAction::class)
        ->execute(User::factory()->create(['role' => 'admin']), $message->fresh(), $reply->fresh());

    contactReplyHandle(contactReplyJob($reply->id));

    expect($reply->fresh()->delivery_status)->toBe(ReplyDeliveryStatus::SENT)
        ->and($message->fresh()->status)->toBe(ContactStatus::CLOSED);
});
