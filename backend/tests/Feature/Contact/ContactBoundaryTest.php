<?php

use App\Constants\ContactConstants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->payload = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Hello',
        'message' => 'This is a test message.',
    ];
});

it('accepts name, email and subject at their max lengths', function () {
    $this->postJson('/api/v1/contact', array_merge($this->payload, [
        'name' => str_repeat('a', ContactConstants::NAME_MAX_LENGTH),
        'subject' => str_repeat('b', ContactConstants::SUBJECT_MAX_LENGTH),
    ]))->assertOk();
});

it('rejects a name one character over the max', function () {
    $this->postJson('/api/v1/contact', array_merge($this->payload, [
        'name' => str_repeat('a', ContactConstants::NAME_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects an over-long email', function () {
    $this->postJson('/api/v1/contact', array_merge($this->payload, [
        'email' => str_repeat('a', ContactConstants::EMAIL_MAX_LENGTH).'@example.com',
    ]))->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('accepts a message at the max length', function () {
    $this->postJson('/api/v1/contact', array_merge($this->payload, [
        'message' => str_repeat('a', ContactConstants::MESSAGE_MAX_LENGTH),
    ]))->assertOk();
});

it('rejects a message one character over the max', function () {
    $this->postJson('/api/v1/contact', array_merge($this->payload, [
        'message' => str_repeat('a', ContactConstants::MESSAGE_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['message']);
});

it('rejects a missing message', function () {
    $payload = $this->payload;
    unset($payload['message']);

    $this->postJson('/api/v1/contact', $payload)
        ->assertStatus(422)->assertJsonValidationErrors(['message']);
});
