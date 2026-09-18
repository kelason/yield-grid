<?php

use Domain\Users\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('restricts unverified users from creating farms', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/farms', [
        'name' => 'My Test Farm',
        'city' => 'Manila',
    ]);

    $response->assertStatus(403)
        ->assertJson(['message' => 'Your email address is not verified.']);
});

it('allows verified users to create farms', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/farms', [
        'name' => 'My Test Farm',
        'city' => 'Manila',
    ]);

    $response->assertStatus(201);
});

it('verifies the user email when a valid signature is provided', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Sanctum::actingAs($user, ['*']);

    // Generate signed URL
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(config('auth.verification.expire', 60)),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    // Call the exact generated URL
    $response = $this->getJson($url);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Email verified successfully']);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('returns 403 when an invalid signature is provided', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Sanctum::actingAs($user, ['*']);

    // Generate a valid URL, but tamper with the signature
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    $tamperedUrl = $url.'123'; // Break the signature

    $response = $this->getJson($tamperedUrl);

    $response->assertStatus(403);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('can resend the verification email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/email/verification-notification');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Verification link sent']);

    Notification::assertSentTo($user, VerifyEmail::class);
});
