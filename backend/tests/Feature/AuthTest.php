<?php

use Domain\Users\Models\User;
use Domain\Users\Enums\UserRole;

test('user can register as a farmer', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'farmer',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['user', 'token']);
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'role' => 'farmer',
    ]);
});

test('user can login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['user', 'token']);
});

test('invalid login returns 401', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});
