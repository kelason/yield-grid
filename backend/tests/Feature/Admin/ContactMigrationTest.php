<?php

use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use Domain\Contact\Models\ContactMessage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('preserves legacy submissions and statuses while accepting closed', function () {
    $legacy = [
        ['status' => ContactStatus::UNREAD->value, 'email' => 'legacy-unread@example.com'],
        ['status' => ContactStatus::READ->value, 'email' => 'legacy-read@example.com'],
        ['status' => ContactStatus::REPLIED->value, 'email' => 'legacy-replied@example.com'],
    ];

    foreach ($legacy as $attributes) {
        DB::table('contact_messages')->insert(array_merge([
            'name' => 'Legacy Sender',
            'subject' => 'Legacy subject',
            'message' => 'Legacy body.',
            'replied_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    expect(ContactMessage::count())->toBe(3)
        ->and(ContactMessage::where('email', 'legacy-read@example.com')->firstOrFail()->status)
        ->toBe(ContactStatus::READ);

    DB::table('contact_messages')->insert([
        'name' => 'Closed Sender',
        'email' => 'legacy-closed@example.com',
        'subject' => 'Closed subject',
        'message' => 'Closed body.',
        'status' => ContactStatus::CLOSED->value,
        'replied_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(ContactMessage::count())->toBe(4);

    $definition = DB::selectOne(
        "SELECT pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conname = 'contact_messages_status_check'"
    );

    expect($definition->definition)->toContain('closed');

    try {
        DB::table('contact_messages')->insert([
            'name' => 'Bogus Sender',
            'email' => 'bogus@example.com',
            'subject' => null,
            'message' => 'Bogus body.',
            'status' => 'archived',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->fail('Expected an invalid status to violate the check constraint.');
    } catch (QueryException) {
        expect(true)->toBeTrue();
    }
});

it('enforces one request key per inquiry for replies', function () {
    $message = ContactMessage::create([
        'name' => 'Key Holder',
        'email' => 'key@example.com',
        'subject' => 'Keys',
        'message' => 'Key body.',
    ]);

    $repository = app(ContactMessageReplyRepositoryInterface::class);
    $attributes = [
        'message_id' => $message->id,
        'admin_id' => null,
        'recipient' => $message->email,
        'body' => 'First body.',
        'client_request_id' => (string) Str::uuid(),
        'delivery_status' => ReplyDeliveryStatus::QUEUED,
        'attempts' => 0,
        'delivery_generation' => 0,
    ];

    $repository->create($attributes);

    try {
        DB::transaction(fn (): ContactMessageReply => $repository->create($attributes));
        $this->fail('Expected a duplicate request key to be rejected.');
    } catch (QueryException) {
        expect(true)->toBeTrue();
    }

    $other = ContactMessage::create([
        'name' => 'Other Holder',
        'email' => 'other@example.com',
        'subject' => 'Keys',
        'message' => 'Other body.',
    ]);

    $repository->create(array_merge($attributes, [
        'message_id' => $other->id,
        'recipient' => $other->email,
    ]));

    $columns = DB::select(
        "SELECT column_name FROM information_schema.columns WHERE table_name = 'contact_message_replies'"
    );
    $names = array_column(array_map(fn ($row): array => (array) $row, $columns), 'column_name');

    foreach (['message_id', 'admin_id', 'recipient', 'body', 'client_request_id', 'delivery_status', 'attempts', 'sending_started_at', 'delivery_generation', 'sent_at', 'error_code'] as $column) {
        expect($names)->toContain($column);
    }
});
