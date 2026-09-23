<?php

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('tests manual listing with true boolean', function () {
    $user = User::factory()->farmer()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/farmer/listings', [
        'title' => 'Test',
        'crop_name' => 'Rice',
        'quantity_kg' => 10,
        'price_per_kg' => 10,
        'estimated_harvest_date' => '',
        'shelf_life_days' => 30,
        'is_harvest_available' => true,
    ]);

    $response->dump();
});
