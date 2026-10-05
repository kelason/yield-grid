<?php

use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function claimPlot(User $farmer): Plot
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

function claimEnrollment(User $farmer): InsuranceEnrollment
{
    return InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'plot_id' => claimPlot($farmer)->id,
        'program' => InsuranceProgram::RICE,
        'season' => Season::WET,
        'season_year' => 2026,
    ]);
}

function claimApiFarmer(): User
{
    $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($farmer, ['*']);

    return $farmer;
}

it('files a claim on an owned enrollment', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);

    $response = $this->postJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/claims", [
        'loss_date' => '2026-08-15',
        'cause' => 'typhoon',
        'description' => 'Lodged palay after Signal #3',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.notice_of_loss_deadline', '2026-08-25')
        ->assertJsonPath('data.notice_of_loss_overdue', true);

    expect(InsuranceClaim::where('enrollment_id', $enrollment->id)->count())->toBe(1);
});

it('rejects claims with a future loss date or overlong description', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);

    $this->postJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/claims", [
        'loss_date' => now()->addDay()->toDateString(),
        'cause' => 'flood',
    ])->assertStatus(422);

    $this->postJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/claims", [
        'loss_date' => '2026-08-15',
        'cause' => 'flood',
        'description' => str_repeat('d', 5001),
    ])->assertStatus(422);
});

it('accepts a claim description of exactly 5000 characters', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);

    $this->postJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/claims", [
        'loss_date' => now()->subDay()->toDateString(),
        'cause' => 'flood',
        'description' => str_repeat('d', 5000),
    ])->assertCreated();
});

it('forbids filing a claim on another farmer enrollment', function () {
    claimApiFarmer();
    $other = claimEnrollment(User::factory()->farmer()->create());

    $this->postJson("/api/v1/farmer/insurance/enrollments/{$other->id}/claims", [
        'loss_date' => '2026-08-15',
        'cause' => 'flood',
    ])->assertForbidden();

    $this->getJson("/api/v1/farmer/insurance/enrollments/{$other->id}/claims")->assertForbidden();
});

it('lists claims for an owned enrollment', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);
    InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => '2026-08-15',
        'cause' => 'drought',
    ]);

    $this->getJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/claims")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.cause', 'drought');
});

it('advances a claim and stamps the notice of loss filing', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);
    $claim = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(3)->toDateString(),
        'cause' => 'typhoon',
    ]);

    $this->patchJson("/api/v1/farmer/insurance/claims/{$claim->id}/advance", [
        'status' => 'notice_of_loss_filed',
    ])->assertOk()->assertJsonPath('data.status', 'notice_of_loss_filed');

    $fresh = $claim->fresh();

    expect($fresh->status)->toBe(ClaimStatus::NOTICE_OF_LOSS_FILED)
        ->and($fresh->notice_of_loss_filed_at)->not->toBeNull();
});

it('rejects an illegal claim status jump', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);
    $claim = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(3)->toDateString(),
        'cause' => 'typhoon',
    ]);

    $this->patchJson("/api/v1/farmer/insurance/claims/{$claim->id}/advance", [
        'status' => 'paid',
    ])->assertStatus(409);

    expect($claim->fresh()->status)->toBe(ClaimStatus::DRAFT);
});

it('records the payout when a claim is marked paid', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);
    $claim = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(60)->toDateString(),
        'cause' => 'flood',
        'status' => ClaimStatus::APPROVED,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/claims/{$claim->id}/advance", [
        'status' => 'paid',
        'paid_amount_php' => 25000.00,
    ])->assertOk()->assertJsonPath('data.paid_amount_php', '25000.00');

    $fresh = $claim->fresh();

    expect($fresh->paid_amount_php)->toEqual(25000.00)
        ->and($fresh->paid_at)->not->toBeNull();
});

it('rejects a zero payout amount', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);
    $claim = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(60)->toDateString(),
        'cause' => 'flood',
        'status' => ClaimStatus::APPROVED,
    ]);

    $this->patchJson("/api/v1/farmer/insurance/claims/{$claim->id}/advance", [
        'status' => 'paid',
        'paid_amount_php' => 0,
    ])->assertStatus(422);

    expect($claim->fresh()->status)->toBe(ClaimStatus::APPROVED);
});

it('rejects a payout amount on non-paid transitions', function () {
    $farmer = claimApiFarmer();
    $enrollment = claimEnrollment($farmer);
    $claim = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(3)->toDateString(),
        'cause' => 'flood',
    ]);

    $this->patchJson("/api/v1/farmer/insurance/claims/{$claim->id}/advance", [
        'status' => 'notice_of_loss_filed',
        'paid_amount_php' => 25000.00,
    ])->assertStatus(422);
});
