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
}
