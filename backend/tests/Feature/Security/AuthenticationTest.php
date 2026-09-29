<?php

// OWASP A07:2021 — Identification and Authentication Failures.

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('rejects logins with a wrong password without leaking a token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonMissing(['token']);
});

it('returns identical errors for unknown emails (no user enumeration)', function () {
    $user = User::factory()->create();

    $knownResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $unknownResponse = $this->postJson('/api/v1/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $knownResponse->assertUnprocessable();
    $unknownResponse->assertUnprocessable();
    expect($unknownResponse->json('errors'))->toEqual($knownResponse->json('errors'));
});

it('rejects expired tokens', function () {
    $user = User::factory()->create();
    $expiredToken = $user->createToken('expired_token', ['*'], now()->subHour())->plainTextToken;

    $this->withToken($expiredToken)
        ->getJson('/api/v1/user')
        ->assertUnauthorized();
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();

    $token = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->json('token');

    $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

    $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
});

it('throttles rapid password-reset requests', function () {
    $user = User::factory()->create();

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
    }

    $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
        ->assertStatus(429);
});
