<?php

namespace Tests\Feature;

use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flags_same_risky_family_as_avoid(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=tomato&crop_b=eggplant')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'avoid')
            ->assertJsonPath('data.rotation.verdict', 'avoid')
            ->assertJsonPath('data.crop_a.slug', 'tomato')
            ->assertJsonPath('data.crop_b.family', 'nightshade');
    }

    public function test_it_flags_clashing_companions_as_avoid(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=red-onion&crop_b=mungbean')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'avoid')
            ->assertJsonPath('data.companion.verdict', 'avoid');
    }

    public function test_it_flags_nightshade_cucurbit_companions_as_avoid(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=potato&crop_b=sayote')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'avoid')
            ->assertJsonPath('data.companion.verdict', 'avoid');
    }

    public function test_it_flags_brassica_nightshade_companions_as_avoid(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=cabbage&crop_b=tomato')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'avoid')
            ->assertJsonPath('data.companion.verdict', 'avoid');
    }

    public function test_it_flags_carrot_dill_clash_with_a_specific_reason(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=carrot&crop_b=dill')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'avoid')
            ->assertJsonPath('data.companion.verdict', 'avoid');

        $reasons = implode(' ', $response->json('data.companion.reasons'));

        $this->assertStringContainsStringIgnoringCase('carrot', $reasons);
        $this->assertStringContainsStringIgnoringCase('dill', $reasons);
    }

    public function test_it_marks_new_same_family_pairs(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=kangkong&crop_b=sweet-potato')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'caution');

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=lemongrass&crop_b=corn')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'avoid')
            ->assertJsonPath('data.rotation.verdict', 'avoid');
    }

    public function test_it_marks_new_unrelated_crops_as_compatible(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=mustard&crop_b=garlic')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'compatible');
    }

    public function test_it_marks_same_safe_family_as_caution(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=calamansi&crop_b=pomelo')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'caution')
            ->assertJsonPath('data.rotation.verdict', 'caution');
    }

    public function test_it_marks_restorative_pairs_as_compatible(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=mungbean&crop_b=tomato')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'compatible')
            ->assertJsonPath('data.rotation.verdict', 'compatible');

        $reasons = implode(' ', $response->json('data.rotation.reasons'));

        $this->assertStringContainsStringIgnoringCase('nitrogen', $reasons);
    }

    public function test_it_marks_unrelated_crops_as_compatible(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=lettuce&crop_b=calamansi')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'compatible');
    }

    public function test_it_accepts_a_crop_checked_against_itself(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=tomato&crop_b=Tomatoes')
            ->assertOk()
            ->assertJsonPath('data.verdict', 'compatible');
    }

    public function test_it_rejects_unknown_crops(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=tomato&crop_b=moon-melon')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['crop_b']);
    }

    public function test_it_requires_both_crops(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)
            ->getJson('/api/v1/crop-compatibility?crop_a=tomato')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['crop_b']);
    }

    public function test_it_forbids_guests_and_buyers(): void
    {
        $this->getJson('/api/v1/crop-compatibility?crop_a=tomato&crop_b=lettuce')
            ->assertUnauthorized();

        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->getJson('/api/v1/crop-compatibility?crop_a=tomato&crop_b=lettuce')
            ->assertForbidden();
    }
}
