<?php

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('verifies the user email when called with url from frontend but different host', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    // Mock APP_URL to be localhost:8000
    config(['app.url' => 'http://localhost:8000']);
    URL::forceRootUrl('http://localhost:8000');

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
    );

    // Send the request but simulate the server receiving a slightly different host or no port
    // e.g. the proxy strips the port
    $response = $this->withHeaders(['Host' => 'localhost'])->getJson($url);

    $response->assertStatus(403);
});
