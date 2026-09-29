<?php

// OWASP A07:2021 — Identification and Authentication Failures.

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

    // Guard instances (and their resolved users) are cached on the shared
    // app container for the whole test — drop them so the next request
    // re-resolves auth instead of reusing the pre-logout user.
    Auth::forgetGuards();

    $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
});

it('throttles rapid password-reset requests', function () {
    // Distinct users: Laravel's broker throttles repeat sends per email,
    // so sharing one address would trip the broker (422) before the route
    // limiter (429) this test targets.
    $users = User::factory()->count(7)->create();

    foreach ($users->take(6) as $user) {
        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
    }

    $this->postJson('/api/v1/forgot-password', ['email' => $users->last()->email])
        ->assertTooManyRequests();
});
