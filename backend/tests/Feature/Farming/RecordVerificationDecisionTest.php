<?php

use App\Policies\FarmVerificationPolicy;
use Domain\Farming\Actions\RecordVerificationDecisionAction;
use Domain\Farming\Enums\VerificationDecision;
use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function verificationAdmin(): User
{
    return User::factory()->create(['role' => UserRole::ADMIN]);
}

function decide(Farm|Plot $target, VerificationDecision $decision, ?VerificationMethod $method = null, ?string $note = null): Farm|Plot
{
    return app(RecordVerificationDecisionAction::class)->execute(
        $target, $decision, $method, $note, verificationAdmin());
}

it('verifies a pending farm with method and audit fields', function () {
    $admin = verificationAdmin();
    $farm = Farm::factory()->create();
    $result = app(RecordVerificationDecisionAction::class)->execute(
        $farm, VerificationDecision::VERIFY, VerificationMethod::FIELD_VISIT, 'Visited site', $admin);
    expect($result->verification_status)->toBe(VerificationStatus::VERIFIED)
        ->and($result->verification_method)->toBe(VerificationMethod::FIELD_VISIT)
        ->and($result->verification_note)->toBe('Visited site')
        ->and($result->verified_by)->toBe($admin->id)
        ->and($result->verified_at)->not->toBeNull();
});

it('verifies a rejected plot without reopening first', function () {
    $plot = Plot::factory()->create(['verification_status' => VerificationStatus::REJECTED]);
    $result = decide($plot, VerificationDecision::VERIFY, VerificationMethod::PHONE_CHECK);
    expect($result->fresh()->verification_status)->toBe(VerificationStatus::VERIFIED);
});

it('rejects a pending plot with a reason', function () {
    $admin = verificationAdmin();
    $plot = Plot::factory()->create();
    $result = app(RecordVerificationDecisionAction::class)->execute(
        $plot, VerificationDecision::REJECT, null, 'No such address', $admin);
    expect($result->verification_status)->toBe(VerificationStatus::REJECTED)
        ->and($result->verification_note)->toBe('No such address')
        ->and($result->verification_method)->toBeNull()
        ->and($result->verified_by)->toBe($admin->id);
});

it('revokes a verified farm with a reason', function () {
    $farm = Farm::factory()->create(['verification_status' => VerificationStatus::VERIFIED]);
    $result = decide($farm, VerificationDecision::REVOKE, null, 'Fraud report');
    expect($result->fresh()->verification_status)->toBe(VerificationStatus::REJECTED)
        ->and($result->fresh()->verification_note)->toBe('Fraud report');
});

it('reopens a rejected farm and clears audit fields', function () {
    $farm = Plot::factory()->create()->farm;
    decide($farm, VerificationDecision::REJECT, null, 'Unclear title');
    $result = decide($farm->fresh(), VerificationDecision::REOPEN);
    expect($result->verification_status)->toBe(VerificationStatus::PENDING)
        ->and($result->verification_method)->toBeNull()
        ->and($result->verification_note)->toBeNull()
        ->and($result->verified_by)->toBeNull()
        ->and($result->verified_at)->toBeNull();
});

it('throws on illegal transitions', function () {
    $verified = Farm::factory()->create(['verification_status' => VerificationStatus::VERIFIED]);
    $pending = Farm::factory()->create();
    expect(fn () => decide($verified, VerificationDecision::VERIFY, VerificationMethod::OTHER))
        ->toThrow(LogicException::class);
    expect(fn () => decide($verified, VerificationDecision::REJECT, null, 'x'))
        ->toThrow(LogicException::class);
    expect(fn () => decide($pending, VerificationDecision::REVOKE, null, 'x'))
        ->toThrow(LogicException::class);
    expect(fn () => decide($pending, VerificationDecision::REOPEN))
        ->toThrow(LogicException::class);
    expect($verified->fresh()->verification_status)->toBe(VerificationStatus::VERIFIED)
        ->and($pending->fresh()->verification_status)->toBe(VerificationStatus::PENDING);
});

it('re-queues a rejected plot on any non-status update', function () {
    $plot = Plot::factory()->create(['verification_status' => VerificationStatus::REJECTED]);
    $plot->update(['name' => 'Corrected plot']);
    expect($plot->fresh()->verification_status)->toBe(VerificationStatus::PENDING);
});

it('re-queues a rejected farm on any non-status update', function () {
    $farm = Farm::factory()->create(['verification_status' => VerificationStatus::REJECTED]);
    $farm->update(['name' => 'Corrected farm']);
    expect($farm->fresh()->verification_status)->toBe(VerificationStatus::PENDING);
});

it('does not re-queue when a decision updates the status', function () {
    $farm = Farm::factory()->create(['verification_status' => VerificationStatus::REJECTED]);
    $result = decide($farm, VerificationDecision::VERIFY, VerificationMethod::FIELD_VISIT, 'Re-checked');
    expect($result->fresh()->verification_status)->toBe(VerificationStatus::VERIFIED);
});

it('allows verified admins in the verification policy', function () {
    $policy = new FarmVerificationPolicy;
    $admin = verificationAdmin();
    $farm = Farm::factory()->create();
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);
    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $farm))->toBeTrue()
        ->and($policy->decide($admin, $farm))->toBeTrue()
        ->and($policy->view($admin, $plot))->toBeTrue()
        ->and($policy->decide($admin, $plot))->toBeTrue();
});

it('denies farmers and unverified admins in the verification policy', function () {
    $policy = new FarmVerificationPolicy;
    $farmer = User::factory()->farmer()->create();
    $unverifiedAdmin = User::factory()->unverified()->create(['role' => UserRole::ADMIN]);
    $farm = Farm::factory()->create();
    expect($policy->viewAny($farmer))->toBeFalse()
        ->and($policy->decide($farmer, $farm))->toBeFalse()
        ->and($policy->viewAny($unverifiedAdmin))->toBeFalse()
        ->and($policy->decide($unverifiedAdmin, $farm))->toBeFalse();
});
