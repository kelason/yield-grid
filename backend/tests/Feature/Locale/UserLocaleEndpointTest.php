<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function localeRegisterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Luz Viminda',
        'email' => 'luz@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ], $overrides);
}

it('updates the own locale preference', function (): void {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->patchJson('/api/v1/user/locale', ['locale' => 'ceb'])
        ->assertOk()
        ->assertExactJson(['locale' => 'ceb']);

    expect($user->fresh()->locale)->toBe('ceb');
});

it('rejects unsupported locales with 422', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/user/locale', ['locale' => 'es'])
        ->assertStatus(422);

    expect($user->fresh()->locale)->toBe('en');
});

it('requires authentication for locale updates', function (): void {
    $this->patchJson('/api/v1/user/locale', ['locale' => 'ceb'])->assertUnauthorized();
});

it('persists the locale sent at registration', function (): void {
    $this->postJson('/api/v1/register', localeRegisterPayload(['locale' => 'tl']))
        ->assertCreated()
        ->assertJsonPath('user.locale', 'tl');

    $this->assertDatabaseHas('users', ['email' => 'luz@example.com', 'locale' => 'tl']);
});

it('defaults the registration locale to English', function (): void {
    $this->postJson('/api/v1/register', localeRegisterPayload())->assertCreated();

    $this->assertDatabaseHas('users', ['email' => 'luz@example.com', 'locale' => 'en']);
});
