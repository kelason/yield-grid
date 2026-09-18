<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('submits a contact message', function () {
    $response = $this->postJson('/api/v1/contact', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Hello',
        'message' => 'This is a test message.',
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Your message has been sent successfully.']);
});
