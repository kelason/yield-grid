<?php

namespace Tests\Feature;

use Domain\Users\Models\User;
use Domain\Users\Enums\UserRole;
use Domain\Farming\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_create_a_plot_with_polygon_coordinates(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $farm = Farm::create([
            'user_id' => $farmer->id,
            'name' => 'Test Farm'
        ]);

        $coordinates = [
            [0, 0],
            [0, 10],
            [10, 10],
            [10, 0],
            [0, 0] // Closed polygon
        ];

        $response = $this->actingAs($farmer)->postJson("/api/v1/farms/{$farm->id}/plots", [
            'name' => 'North Field',
            'soil_type' => 'loamy',
            'coordinates' => $coordinates,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'North Field')
                 ->assertJsonPath('data.soil_type', 'loamy');

        $this->assertDatabaseHas('plots', [
            'farm_id' => $farm->id,
            'name' => 'North Field',
        ]);
        
        $indexResponse = $this->actingAs($farmer)->getJson("/api/v1/farms/{$farm->id}/plots");
        
        $indexResponse->assertStatus(200)
                      ->assertJsonPath('type', 'FeatureCollection')
                      ->assertJsonPath('features.0.properties.name', 'North Field');
    }

    public function test_cannot_create_plot_outside_farm_registered_city(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);
        $farm = Farm::create([
            'user_id' => $farmer->id,
            'name' => 'Cabanatuan Farm',
            'city' => 'Cabanatuan City',
            'state' => 'Nueva Ecija',
            'country' => 'Philippines',
        ]);

        // Mock cache for Tarlac coordinates
        \Illuminate\Support\Facades\Cache::put('geo_coord_15.48_120.598', [
            'city' => 'Tarlac City',
            'state' => 'Tarlac',
            'country' => 'Philippines',
        ], 3600);

        $coordinatesInTarlac = [
            [120.597, 15.480],
            [120.599, 15.480],
            [120.599, 15.481],
            [120.597, 15.481],
            [120.597, 15.480],
        ];

        $response = $this->actingAs($farmer)->postJson("/api/v1/farms/{$farm->id}/plots", [
            'name' => 'Mismatched Plot',
            'soil_type' => 'loamy',
            'coordinates' => $coordinatesInTarlac,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['coordinates']);
    }
}
