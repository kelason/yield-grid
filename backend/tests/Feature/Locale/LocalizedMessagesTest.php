<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('denies a Cebuano farmer in Cebuano on buyer-only routes', function (): void {
    $farmer = User::factory()->farmer()->create(['locale' => 'ceb']);

    $this->actingAs($farmer)->getJson('/api/v1/buyer/demands')
        ->assertForbidden()
        ->assertExactJson(['message' => 'Walay pagtugot o dili igo ang katungod.']);
});

it('returns Tagalog validation errors for Tagalog guests', function (): void {
    $this->postJson('/api/v1/register', [
        'name' => 'Luz Viminda',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ], ['Accept-Language' => 'tl'])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'Ang email ay kailangan.');
});

it('keeps English responses byte-identical', function (): void {
    $farmer = User::factory()->farmer()->create(['locale' => 'en']);

    $this->actingAs($farmer)->getJson('/api/v1/buyer/demands')
        ->assertForbidden()
        ->assertExactJson(['message' => 'Unauthorized or insufficient permissions.']);

    $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ])->assertStatus(422)->assertJsonPath('errors.email.0', 'The email field is required.');
});
