<?php

use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceProfile;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function insuranceApiPlot(User $farmer): Plot
{
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);

    return Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Test Plot',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 1.5,
    ]);
}

function insuranceApiFarmer(): User
{
    $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($farmer, ['*']);

    return $farmer;
}

it('creates a default RSBSA profile on first view', function () {
    $farmer = insuranceApiFarmer();

    $response = $this->getJson('/api/v1/farmer/insurance/profile');

    $response->assertOk()
        ->assertJsonPath('data.rsbsa_status', 'not_registered')
        ->assertJsonPath('data.rsbsa_number', null);

    expect(InsuranceProfile::where('user_id', $farmer->id)->count())->toBe(1);
});

it('updates the RSBSA profile', function () {
    $farmer = insuranceApiFarmer();

    $response = $this->putJson('/api/v1/farmer/insurance/profile', [
        'rsbsa_number' => 'RSBSA-01-234567',
        'rsbsa_status' => 'registered',
    ]);

    $response->assertOk()->assertJsonPath('data.rsbsa_number', 'RSBSA-01-234567');

    expect(InsuranceProfile::where('user_id', $farmer->id)->firstOrFail()->rsbsa_number)
        ->toBe('RSBSA-01-234567');
});

it('rejects an overlong RSBSA number', function () {
    insuranceApiFarmer();

    $this->putJson('/api/v1/farmer/insurance/profile', [
        'rsbsa_number' => str_repeat('9', 31),
        'rsbsa_status' => 'registered',
    ])->assertStatus(422);
});

it('creates an enrollment for an owned plot', function () {
    $farmer = insuranceApiFarmer();
    $plot = insuranceApiPlot($farmer);

    $response = $this->postJson('/api/v1/farmer/insurance/enrollments', [
        'plot_id' => $plot->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
        'notes' => 'First PCIC enrollment',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.pack_status', 'none');

    expect(InsuranceEnrollment::where('user_id', $farmer->id)->count())->toBe(1);
});

it('rejects an enrollment for another farmer plot', function () {
    $farmer = insuranceApiFarmer();
    $otherPlot = insuranceApiPlot(User::factory()->farmer()->create());

    $this->postJson('/api/v1/farmer/insurance/enrollments', [
        'plot_id' => $otherPlot->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
    ])->assertStatus(409);
});

it('rejects enrollment input outside allowed bounds', function () {
    $farmer = insuranceApiFarmer();
    $plot = insuranceApiPlot($farmer);

    $payload = [
        'plot_id' => $plot->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2019,
    ];

    $this->postJson('/api/v1/farmer/insurance/enrollments', $payload)->assertStatus(422);

    $payload['season_year'] = 2026;
    $payload['notes'] = str_repeat('n', 1001);

    $this->postJson('/api/v1/farmer/insurance/enrollments', $payload)->assertStatus(422);
});

it('lists only the farmer own enrollments', function () {
    $farmer = insuranceApiFarmer();
    InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
    ]);
    InsuranceEnrollment::create([
        'user_id' => User::factory()->farmer()->create()->id,
        'program' => 'corn',
        'season' => 'dry',
        'season_year' => 2026,
    ]);

    $this->getJson('/api/v1/farmer/insurance/enrollments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.program', 'rice');
});

it('forbids viewing another farmer enrollment', function () {
    insuranceApiFarmer();
    $other = InsuranceEnrollment::create([
        'user_id' => User::factory()->farmer()->create()->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
    ]);

    $this->getJson("/api/v1/farmer/insurance/enrollments/{$other->id}")->assertForbidden();
});

it('advances enrollment status through a valid transition', function () {
    $farmer = insuranceApiFarmer();
    $enrollment = InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
        'pack_status' => PackStatus::READY,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/status", [
        'status' => 'documents_ready',
    ])->assertOk()->assertJsonPath('data.status', 'documents_ready');

    $this->patchJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/status", [
        'status' => 'submitted_to_mao',
    ])->assertOk();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::SUBMITTED_TO_MAO);
});

it('rejects documents-ready without a generated pack', function () {
    $farmer = insuranceApiFarmer();
    $enrollment = InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/status", [
        'status' => 'documents_ready',
    ])->assertStatus(409);

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::DRAFT);
});

it('rejects an illegal enrollment status jump', function () {
    $farmer = insuranceApiFarmer();
    $enrollment = InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/status", [
        'status' => 'active',
    ])->assertStatus(409);

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::DRAFT);
});

it('records policy details and activates a submitted enrollment', function () {
    $farmer = insuranceApiFarmer();
    $enrollment = InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
        'status' => EnrollmentStatus::SUBMITTED_TO_MAO,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/policy-details", [
        'cic_number' => 'CIC-2026-000123',
        'coverage_amount_php' => 37500.00,
        'enrolled_at' => '2026-06-01',
        'expires_at' => '2026-11-30',
    ])->assertOk()->assertJsonPath('data.status', 'active');

    $fresh = $enrollment->fresh();

    expect($fresh->cic_number)->toBe('CIC-2026-000123')
        ->and($fresh->coverage_amount_php)->toEqual(37500.00)
        ->and($fresh->status)->toBe(EnrollmentStatus::ACTIVE);
});

it('rejects policy details before MAO submission', function () {
    $farmer = insuranceApiFarmer();
    $enrollment = InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/policy-details", [
        'cic_number' => 'CIC-2026-000123',
    ])->assertStatus(409);

    expect($enrollment->fresh()->cic_number)->toBeNull();
});

it('rejects policy details outside allowed bounds', function () {
    $farmer = insuranceApiFarmer();
    $enrollment = InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'program' => 'rice',
        'season' => 'wet',
        'season_year' => 2026,
        'status' => EnrollmentStatus::SUBMITTED_TO_MAO,
    ]);
    $url = "/api/v1/farmer/insurance/enrollments/{$enrollment->id}/policy-details";

    $this->patchJson($url, ['cic_number' => str_repeat('C', 31)])->assertStatus(422);
    $this->patchJson($url, ['cic_number' => 'CIC-1', 'coverage_amount_php' => -5])->assertStatus(422);
    $this->patchJson($url, [
        'cic_number' => 'CIC-1',
        'enrolled_at' => '2026-06-01',
        'expires_at' => '2026-05-01',
    ])->assertStatus(422);
});

it('forbids buyers from insurance endpoints', function () {
    $buyer = User::factory()->buyer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($buyer, ['*']);

    $this->getJson('/api/v1/farmer/insurance/profile')->assertForbidden();
    $this->getJson('/api/v1/farmer/insurance/enrollments')->assertForbidden();
});
