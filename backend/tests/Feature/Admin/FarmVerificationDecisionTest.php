<?php

use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function decisionTokenFor(User $user): string
{
    Auth::forgetGuards();

    return $user->createToken('decision-test-token')->plainTextToken;
}

function decisionAdminUser(): User
{
    return User::factory()->create(['role' => UserRole::ADMIN]);
}

it('verifies a farm with method and note', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => VerificationMethod::FIELD_VISIT->value, 'note' => 'Visited site'])
        ->assertOk()
        ->assertJsonPath('data.verification_status', VerificationStatus::VERIFIED->value)
        ->assertJsonPath('data.verification_method', VerificationMethod::FIELD_VISIT->value)
        ->assertJsonPath('data.verification_note', 'Visited site')
        ->assertJsonPath('data.verified_by', $admin->id);
});

it('verifies a plot with a method', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $plot = Plot::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/plots/{$plot->id}/verify",
        ['method' => VerificationMethod::PHONE_CHECK->value])
        ->assertOk()
        ->assertJsonPath('data.verification_status', VerificationStatus::VERIFIED->value);
});

it('rejects verify without a method or with a bad method', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify", [])
        ->assertUnprocessable();
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => 'satellite_guess'])->assertUnprocessable();
    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => VerificationMethod::OTHER->value, 'note' => str_repeat('n', 1001)])
        ->assertUnprocessable();
});

it('rejects a farm decision without a reason', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/reject", [])
        ->assertUnprocessable();
});

it('accepts a reason at the limit and rejects one character over', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create();
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/reject",
        ['reason' => str_repeat('a', 1000)])
        ->assertOk()
        ->assertJsonPath('data.verification_status', VerificationStatus::REJECTED->value);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/plots/{$plot->id}/reject",
        ['reason' => str_repeat('a', 1001)])->assertUnprocessable();
    expect($plot->fresh()->verification_status)->toBe(VerificationStatus::PENDING);
});

it('revokes a verified farm with a reason', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create(['verification_status' => VerificationStatus::VERIFIED]);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/revoke",
        ['reason' => 'Fraud report'])
        ->assertOk()
        ->assertJsonPath('data.verification_status', VerificationStatus::REJECTED->value)
        ->assertJsonPath('data.verification_note', 'Fraud report');
});

it('reopens a rejected farm and clears audit fields', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create(['verification_status' => VerificationStatus::REJECTED]);

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/reopen")
        ->assertOk()
        ->assertJsonPath('data.verification_status', VerificationStatus::PENDING->value)
        ->assertJsonPath('data.verification_note', null)
        ->assertJsonPath('data.verified_by', null);
});

it('returns 409 when verifying twice and keeps the original timestamp', function () {
    $admin = decisionAdminUser();
    $token = decisionTokenFor($admin);
    $farm = Farm::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => VerificationMethod::FIELD_VISIT->value])->assertOk();
    $verifiedAt = $farm->fresh()->verified_at->toIso8601String();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => VerificationMethod::PHONE_CHECK->value])->assertConflict();
    expect($farm->fresh()->verified_at->toIso8601String())->toBe($verifiedAt)
        ->and($farm->fresh()->verification_method)->toBe(VerificationMethod::FIELD_VISIT);
});

it('denies farmers on decisions', function () {
    $farmer = User::factory()->farmer()->create();
    $token = decisionTokenFor($farmer);
    $farm = Farm::factory()->create();

    Auth::forgetGuards();
    $this->withToken($token)->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => VerificationMethod::OTHER->value])->assertForbidden();
});

it('denies guests on decisions', function () {
    $farm = Farm::factory()->create();

    $this->postJson("/api/v1/admin/verifications/farms/{$farm->id}/verify",
        ['method' => VerificationMethod::OTHER->value])->assertUnauthorized();
});
