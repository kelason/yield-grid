<?php

namespace Tests\Feature\Auth;

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_password_reset_link()
    {
        $user = clone User::factory()->create();

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);
    }

    public function test_can_reset_password_with_valid_token()
    {
        $user = clone User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        // Check if password works
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'new-password-123',
        ]);

        $loginResponse->assertStatus(200);
    }
}
