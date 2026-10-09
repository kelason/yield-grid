<?php

use App\Constants\FarmingConstants;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('defaults new farms and plots to pending verification', function () {
    $farm = Farm::factory()->create();
    expect($farm->verification_status)->toBe(VerificationStatus::PENDING)
        ->and($farm->verified_by)->toBeNull();
    $plot = Plot::factory()->create(['farm_id' => $farm->id]);
    expect($plot->verification_status)->toBe(VerificationStatus::PENDING);
});

it('exposes the verification enums and note limit', function () {
    expect(VerificationStatus::values())->toBe(['pending', 'verified', 'rejected'])
        ->and(FarmingConstants::VERIFICATION_NOTE_MAX_LENGTH)->toBe(1000);
});
