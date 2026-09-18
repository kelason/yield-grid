<?php

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns restricted zones as geojson', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    // Create a restricted zone using DB raw since it has polygon
    DB::insert("
        INSERT INTO restricted_zones (name, type, polygon, created_at, updated_at) 
        VALUES ('Test Zone', 'no-build', ST_GeomFromText('POLYGON((0 0, 10 0, 10 10, 0 10, 0 0))', 4326), now(), now())
    ");

    $response = $this->getJson('/api/v1/restricted-zones');

    $response->assertStatus(200)
        ->assertJson([
            'type' => 'FeatureCollection',
        ])
        ->assertJsonPath('features.0.properties.name', 'Test Zone');
});
