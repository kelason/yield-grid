<?php

namespace Tests\Feature;

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
}
