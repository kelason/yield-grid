<?php

namespace Tests\Feature;

use Domain\Farming\Models\Farm;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_create_a_farm(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);

        $response = $this->actingAs($farmer)->postJson('/api/v1/farms', [
            'name' => 'Green Acres',
            'city' => 'Springfield',
            'total_area' => 150.5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Green Acres');

        $this->assertDatabaseHas('farms', [
            'user_id' => $farmer->id,
            'name' => 'Green Acres',
            'city' => 'Springfield',
        ]);
    }

    public function test_buyer_cannot_create_a_farm(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER]);

        $response = $this->actingAs($buyer)->postJson('/api/v1/farms', [
            'name' => 'Should Fail',
        ]);

        $response->assertStatus(403);
    }

    public function test_farmer_can_list_their_own_farms(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);

        Farm::create([
            'user_id' => $farmer->id,
            'name' => 'Farm A',
            'city' => 'Manila',
        ]);

        Farm::create([
            'user_id' => $farmer->id,
            'name' => 'Farm B',
            'city' => 'Quezon City',
        ]);

        $response = $this->actingAs($farmer)->getJson('/api/v1/farms');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_farmer_cannot_see_other_farmers_farms(): void
    {
        $farmer1 = User::factory()->create(['role' => UserRole::FARMER]);
        $farmer2 = User::factory()->create(['role' => UserRole::FARMER]);

        Farm::create([
            'user_id' => $farmer1->id,
            'name' => 'Farmer 1 Farm',
            'city' => 'Manila',
        ]);

        $response = $this->actingAs($farmer2)->getJson('/api/v1/farms');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_farm_creation_requires_valid_data(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER]);

        $response = $this->actingAs($farmer)->postJson('/api/v1/farms', [
            'name' => '', // Empty name should fail
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
