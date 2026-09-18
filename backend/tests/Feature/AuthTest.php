<?php

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('registers a new user', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'farmer',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'role' => 'farmer',
    ]);
});

it('logs in a user', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ]);
});

it('logs out a user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/user');

    $response->assertStatus(200)
        ->assertJsonPath('email', $user->email);
});
