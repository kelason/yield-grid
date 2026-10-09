<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('defaults new users to the English locale', function (): void {
    $user = User::factory()->create();

    expect($user->locale)->toBe('en');
});

it('exposes the locale in the user payload', function (): void {
    $user = User::factory()->create(['locale' => 'ceb']);

    $this->actingAs($user)->getJson('/api/v1/user')->assertOk()->assertJsonPath('locale', 'ceb');
});
