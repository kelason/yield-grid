<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('exposes status to farmers but hides admin-only fields', function () {
    $farmer = User::factory()->farmer()->create();
    Farm::factory()->create(['user_id' => $farmer->id,
        'verification_status' => VerificationStatus::VERIFIED]);
    $this->actingAs($farmer)->getJson('/api/v1/farms')->assertOk()
        ->assertJsonPath('data.0.verification_status', VerificationStatus::VERIFIED->value)
        ->assertJsonMissingPath('data.0.verification_method')
        ->assertJsonMissingPath('data.0.verified_by')
        ->assertJsonMissingPath('data.0.verified_at')
        ->assertJsonMissingPath('data.0.verification_note');
});

it('includes the rejection reason on rejected farms', function () {
    $farmer = User::factory()->farmer()->create();
    Farm::factory()->create(['user_id' => $farmer->id,
        'verification_status' => VerificationStatus::REJECTED,
        'verification_note' => 'No such address']);
    $this->actingAs($farmer)->getJson('/api/v1/farms')->assertOk()
        ->assertJsonPath('data.0.verification_status', VerificationStatus::REJECTED->value)
        ->assertJsonPath('data.0.verification_note', 'No such address');
});

it('includes the parent farm verification status on plots', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id,
        'name' => 'Home Farm', 'verification_status' => VerificationStatus::VERIFIED]);
    Plot::factory()->create(['farm_id' => $farm->id, 'name' => 'North Field']);
    $this->actingAs($farmer)->getJson('/api/v1/plots')->assertOk()
        ->assertJsonPath('data.0.name', 'North Field')
        ->assertJsonPath('data.0.verification_status', VerificationStatus::PENDING->value)
        ->assertJsonPath('data.0.farm.id', $farm->id)
        ->assertJsonPath('data.0.farm.name', 'Home Farm')
        ->assertJsonPath('data.0.farm.verification_status', VerificationStatus::VERIFIED->value)
        ->assertJsonMissingPath('data.0.verified_by');
});

it('flags recommendations from a verified plot and farm', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id,
        'verification_status' => VerificationStatus::VERIFIED]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id,
        'verification_status' => VerificationStatus::VERIFIED]);
    CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
    $this->actingAs($farmer)->getJson("/api/v1/plots/{$plot->id}/recommendations")->assertOk()
        ->assertJsonPath('data.0.is_from_verified_source', true);
});

it('does not flag recommendations from a pending plot', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::factory()->create(['user_id' => $farmer->id,
        'verification_status' => VerificationStatus::VERIFIED]);
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);
    CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);
    $this->actingAs($farmer)->getJson("/api/v1/plots/{$plot->id}/recommendations")->assertOk()
        ->assertJsonPath('data.0.is_from_verified_source', false);
});
