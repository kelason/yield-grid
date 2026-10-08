<?php

use App\Constants\AuthConstants;
use Domain\Users\Actions\RegisterUserAction;
use Domain\Users\DTOs\RegisterUserDTO;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->registerPayload = [
        'name' => 'Boundary Tester',
        'email' => 'boundary@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ];
});

it('accepts registration at name, email and password max lengths', function () {
    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'name' => str_repeat('a', AuthConstants::NAME_MAX_LENGTH),
        'email' => str_repeat('b', AuthConstants::EMAIL_MAX_LENGTH - 12).'@example.com',
        'password' => str_repeat('c', AuthConstants::PASSWORD_MAX_LENGTH),
        'password_confirmation' => str_repeat('c', AuthConstants::PASSWORD_MAX_LENGTH),
    ]))->assertCreated();
});

it('rejects registration with a name one character over the max', function () {
    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'name' => str_repeat('a', AuthConstants::NAME_MAX_LENGTH + 1),
    ]))->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('rejects registration with an email over the max', function () {
    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'email' => str_repeat('a', AuthConstants::EMAIL_MAX_LENGTH).'@example.com',
    ]))->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('rejects registration with a password one character over the max', function () {
    $long = str_repeat('a', AuthConstants::PASSWORD_MAX_LENGTH + 1);

    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'password' => $long,
        'password_confirmation' => $long,
    ]))->assertStatus(422)->assertJsonValidationErrors(['password']);
});

it('rejects registration with the admin role', function () {
    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'role' => 'admin',
    ]))->assertStatus(422)->assertJsonValidationErrors(['role']);
});

it('rejects registration with an unknown role', function () {
    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'role' => 'superuser',
    ]))->assertStatus(422)->assertJsonValidationErrors(['role']);
});

it('rejects non-member roles in RegisterUserAction directly', function () {
    $action = app(RegisterUserAction::class);

    expect(fn () => $action(new RegisterUserDTO('Admin Attempt', 'admin-attempt@example.com', 'password123', 'admin')))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $action(new RegisterUserDTO('Unknown Attempt', 'unknown-attempt@example.com', 'password123', 'superuser')))
        ->toThrow(InvalidArgumentException::class);

    expect(User::count())->toBe(0);
});

it('persists farmer and buyer roles for successful registrations', function () {
    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'email' => 'member-farmer@example.com',
        'role' => 'farmer',
    ]))->assertCreated();

    $this->postJson('/api/v1/register', array_merge($this->registerPayload, [
        'email' => 'member-buyer@example.com',
        'role' => 'buyer',
    ]))->assertCreated();

    expect(User::where('email', 'member-farmer@example.com')->firstOrFail()->role)->toBe(UserRole::FARMER)
        ->and(User::where('email', 'member-buyer@example.com')->firstOrFail()->role)->toBe(UserRole::BUYER);
});

it('rejects login with an email over the max', function () {
    $this->postJson('/api/v1/login', [
        'email' => str_repeat('a', AuthConstants::EMAIL_MAX_LENGTH).'@example.com',
        'password' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('rejects a password-reset request with an email over the max', function () {
    $this->postJson('/api/v1/forgot-password', [
        'email' => str_repeat('a', AuthConstants::EMAIL_MAX_LENGTH).'@example.com',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('rejects a password reset with an over-long token', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/v1/reset-password', [
        'token' => str_repeat('a', AuthConstants::RESET_TOKEN_MAX_LENGTH + 1),
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertStatus(422)->assertJsonValidationErrors(['token']);
});
