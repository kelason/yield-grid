<?php

use App\Constants\ContactConstants;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function contactInboxAdminToken(): string
{
    Auth::forgetGuards();

    $admin = User::factory()->create(['role' => 'admin']);

    return $admin->createToken('contact-inbox-test-token')->plainTextToken;
}

function contactInboxMessage(array $attributes = []): ContactMessage
{
    return ContactMessage::create(array_merge([
        'name' => 'Jane Inquirer',
        'email' => 'jane@example.com',
        'subject' => 'Harvest question',
        'message' => 'When is the next harvest?',
    ], $attributes));
}

function contactInboxReply(ContactMessage $message, array $attributes = []): ContactMessageReply
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

it('denies guests and farmers on the inquiry endpoints', function () {
    $message = contactInboxMessage();

    $this->getJson('/api/v1/admin/contact-messages')->assertUnauthorized();
    $this->getJson("/api/v1/admin/contact-messages/{$message->id}")->assertUnauthorized();

    $farmer = User::factory()->farmer()->create();
    Auth::forgetGuards();
    $token = $farmer->createToken('farmer-token')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/admin/contact-messages')->assertForbidden();

    Auth::forgetGuards();
    $this->withToken($token)->getJson("/api/v1/admin/contact-messages/{$message->id}")->assertForbidden();
});

it('lists inquiries newest first with decimal string ids and no-store headers', function () {
    $token = contactInboxAdminToken();
    $older = contactInboxMessage(['email' => 'older@example.com']);
    $newer = contactInboxMessage(['email' => 'newer@example.com']);
    contactInboxReply($newer);

    $response = $this->withToken($token)->getJson('/api/v1/admin/contact-messages');

    $response->assertOk()
        ->assertJsonPath('data.0.id', (string) $newer->id)
        ->assertJsonPath('data.1.id', (string) $older->id)
        ->assertJsonPath('data.0.email', 'newer@example.com')
        ->assertJsonCount(1, 'data.0.replies')
        ->assertJsonPath('data.0.replies.0.message_id', (string) $newer->id)
        ->assertJsonCount(0, 'data.1.replies');

    $cacheControl = (string) $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('no-store')->and($cacheControl)->toContain('private');
});

it('leaves unread unchanged on GET detail and records no history', function () {
    $token = contactInboxAdminToken();
    $message = contactInboxMessage();

    $response = $this->withToken($token)->getJson("/api/v1/admin/contact-messages/{$message->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', (string) $message->id)
        ->assertJsonPath('data.status', ContactStatus::UNREAD->value);

    expect($message->fresh()->status)->toBe(ContactStatus::UNREAD)
        ->and(AdminActionLog::count())->toBe(0);
});

it('returns 404 for missing inquiries after admin checks pass', function () {
    $token = contactInboxAdminToken();

    $this->withToken($token)->getJson('/api/v1/admin/contact-messages/999999')->assertNotFound();

    Auth::forgetGuards();
    $this->withToken($token)->postJson('/api/v1/admin/contact-messages/999999/read')->assertNotFound();
});

it('filters the inbox by status and search with bounded pagination', function () {
    $token = contactInboxAdminToken();
    contactInboxMessage(['status' => ContactStatus::READ, 'subject' => 'Alfalfa pricing']);
    contactInboxMessage(['status' => ContactStatus::UNREAD, 'subject' => 'Barley pricing']);

    $response = $this->withToken($token)->getJson('/api/v1/admin/contact-messages?status=read');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', ContactStatus::READ->value);

    Auth::forgetGuards();
    $search = $this->withToken($token)->getJson('/api/v1/admin/contact-messages?search=alfalfa');

    $search->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.subject', 'Alfalfa pricing');

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/contact-messages?status=bogus')->assertStatus(422);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/contact-messages?search='.str_repeat('s', 101))->assertStatus(422);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/contact-messages?page=0')->assertStatus(422);

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/admin/contact-messages?per_page=101')->assertStatus(422);
});

it('respects per_page while reporting the full total', function () {
    $token = contactInboxAdminToken();
    contactInboxMessage(['email' => 'one@example.com']);
    contactInboxMessage(['email' => 'two@example.com']);
    contactInboxMessage(['email' => 'three@example.com']);

    $response = $this->withToken($token)->getJson('/api/v1/admin/contact-messages?per_page=2');

    $response->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 3);
});

it('marks unread as read once and rejects repeated or invalid reads', function () {
    Bus::fake();
    $token = contactInboxAdminToken();
    $message = contactInboxMessage();

    $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/read");

    $response->assertOk()->assertJsonPath('data.status', ContactStatus::READ->value);
    expect($message->fresh()->status)->toBe(ContactStatus::READ);

    $history = AdminActionLog::where('action', AdminAction::CONTACT_READ->value)->firstOrFail();
    expect($history->subject_id)->toBe((string) $message->id)
        ->and($history->before)->toBe(['status' => ContactStatus::UNREAD->value])
        ->and($history->after)->toBe(['status' => ContactStatus::READ->value]);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/read")->assertStatus(409);

    foreach ([ContactStatus::REPLIED, ContactStatus::CLOSED] as $status) {
        $other = contactInboxMessage(['status' => $status]);
        Auth::forgetGuards();
        $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$other->id}/read")->assertStatus(409);
    }
});

it('closes any open inquiry and rejects repeated closes', function () {
    Bus::fake();
    $token = contactInboxAdminToken();

    foreach ([ContactStatus::UNREAD, ContactStatus::READ, ContactStatus::REPLIED] as $status) {
        $message = contactInboxMessage(['status' => $status]);
        Auth::forgetGuards();
        $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/close");

        $response->assertOk()->assertJsonPath('data.status', ContactStatus::CLOSED->value);
        expect($message->fresh()->status)->toBe(ContactStatus::CLOSED);
    }

    expect(AdminActionLog::where('action', AdminAction::CONTACT_CLOSED->value)->count())->toBe(3);

    $closed = contactInboxMessage(['status' => ContactStatus::CLOSED]);
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$closed->id}/close")->assertStatus(409);
});

it('blocks close while a queued or sending reply is outstanding', function () {
    Bus::fake();
    $token = contactInboxAdminToken();

    $queued = contactInboxMessage();
    contactInboxReply($queued, ['delivery_status' => ReplyDeliveryStatus::QUEUED]);
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$queued->id}/close")->assertStatus(409);
    expect($queued->fresh()->status)->toBe(ContactStatus::UNREAD);

    $sending = contactInboxMessage();
    contactInboxReply($sending, [
        'delivery_status' => ReplyDeliveryStatus::SENDING,
        'delivery_generation' => 1,
        'sending_started_at' => now(),
    ]);
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$sending->id}/close")->assertStatus(409);

    $failed = contactInboxMessage();
    contactInboxReply($failed, [
        'delivery_status' => ReplyDeliveryStatus::FAILED,
        'error_code' => ContactConstants::REPLY_ERROR_TRANSPORT_FAILED,
    ]);
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$failed->id}/close")->assertOk();

    $sent = contactInboxMessage(['status' => ContactStatus::REPLIED]);
    contactInboxReply($sent, ['delivery_status' => ReplyDeliveryStatus::SENT, 'sent_at' => now()]);
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$sent->id}/close")->assertOk();
});

it('reopens closed inquiries to read while preserving reply history', function () {
    Bus::fake();
    $token = contactInboxAdminToken();
    $message = contactInboxMessage(['status' => ContactStatus::CLOSED, 'replied_at' => now()->subDay()]);
    $reply = contactInboxReply($message, ['delivery_status' => ReplyDeliveryStatus::SENT, 'sent_at' => now()->subDay()]);

    $response = $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$message->id}/reopen");

    $response->assertOk()->assertJsonPath('data.status', ContactStatus::READ->value);
    expect($message->fresh()->status)->toBe(ContactStatus::READ)
        ->and($message->fresh()->replied_at)->not->toBeNull()
        ->and($reply->fresh())->not->toBeNull();

    expect(AdminActionLog::where('action', AdminAction::CONTACT_REOPENED->value)->count())->toBe(1);

    foreach ([ContactStatus::UNREAD, ContactStatus::READ, ContactStatus::REPLIED] as $status) {
        $other = contactInboxMessage(['status' => $status]);
        Auth::forgetGuards();
        $this->withToken($token)->postJson("/api/v1/admin/contact-messages/{$other->id}/reopen")->assertStatus(409);
    }
});

it('rejects retrying another inquiry reply as missing', function () {
    Bus::fake();
    $token = contactInboxAdminToken();
    $first = contactInboxMessage();
    $second = contactInboxMessage();
    $reply = contactInboxReply($first, ['delivery_status' => ReplyDeliveryStatus::FAILED]);

    Auth::forgetGuards();
    $this->withToken($token)
        ->postJson("/api/v1/admin/contact-messages/{$second->id}/replies/{$reply->id}/retry")
        ->assertNotFound();
});
