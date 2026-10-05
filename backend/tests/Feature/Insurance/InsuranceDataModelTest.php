<?php

use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\LossCause;
use App\Domain\Insurance\Enums\ReminderType;
use App\Domain\Insurance\Enums\RsbsaStatus;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceOffice;
use App\Domain\Insurance\Models\InsuranceProfile;
use App\Domain\Insurance\Models\InsuranceReminderLog;
use App\Domain\Insurance\Models\PlantingWindow;
use Database\Seeders\InsuranceOfficeSeeder;
use Database\Seeders\PlantingWindowSeeder;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function insurancePlot(User $farmer): Plot
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

function insuranceEnrollment(User $farmer, ?Plot $plot = null): InsuranceEnrollment
{
    return InsuranceEnrollment::create([
        'user_id' => $farmer->id,
        'plot_id' => $plot?->id,
        'program' => InsuranceProgram::RICE,
        'season' => Season::WET,
        'season_year' => 2026,
    ]);
}

it('stores a farmer RSBSA profile', function () {
    $farmer = User::factory()->farmer()->create();

    $profile = InsuranceProfile::create([
        'user_id' => $farmer->id,
        'rsbsa_number' => 'RSBSA-01-234567',
        'rsbsa_status' => RsbsaStatus::REGISTERED,
    ]);

    expect($profile->rsbsa_status)->toBe(RsbsaStatus::REGISTERED)
        ->and($profile->user->id)->toBe($farmer->id)
        ->and(InsuranceProfile::where('user_id', $farmer->id)->count())->toBe(1);
});

it('creates an enrollment in draft status linked to a plot', function () {
    $farmer = User::factory()->farmer()->create();
    $plot = insurancePlot($farmer);

    $enrollment = insuranceEnrollment($farmer, $plot);

    expect($enrollment->status)->toBe(EnrollmentStatus::DRAFT)
        ->and($enrollment->program)->toBe(InsuranceProgram::RICE)
        ->and($enrollment->plot->id)->toBe($plot->id)
        ->and($enrollment->user->id)->toBe($farmer->id);
});

it('tracks a claim through its lifecycle fields', function () {
    $farmer = User::factory()->farmer()->create();
    $enrollment = insuranceEnrollment($farmer);

    $claim = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => '2026-08-15',
        'cause' => LossCause::TYPHOON,
        'description' => 'Lodged palay after Signal #3',
    ]);

    expect($claim->status)->toBe(ClaimStatus::DRAFT)
        ->and($claim->enrollment->id)->toBe($enrollment->id);

    $claim->update([
        'status' => ClaimStatus::NOTICE_OF_LOSS_FILED,
        'notice_of_loss_filed_at' => '2026-08-18 10:00:00',
    ]);

    expect($claim->fresh()->status)->toBe(ClaimStatus::NOTICE_OF_LOSS_FILED)
        ->and($claim->fresh()->notice_of_loss_filed_at)->not->toBeNull();
});

it('seeds national planting windows with a Caraga override', function () {
    $this->seed(PlantingWindowSeeder::class);

    $national = PlantingWindow::where('region_code', 'NATIONAL')
        ->where('program', InsuranceProgram::RICE)
        ->where('season', Season::WET)
        ->firstOrFail();

    expect($national->window_start_month)->toBe(5)
        ->and($national->window_end_month)->toBe(7);

    $caraga = PlantingWindow::where('region_code', '160000000')
        ->where('program', InsuranceProgram::RICE)
        ->where('season', Season::WET)
        ->firstOrFail();

    expect($caraga->source)->toContain('philrice')
        ->and(PlantingWindow::count())->toBeGreaterThan(4);
});

it('seeds the PCIC office directory with head office and regions', function () {
    $this->seed(InsuranceOfficeSeeder::class);

    $headOffice = InsuranceOffice::whereNull('region_code')->firstOrFail();

    expect($headOffice->name)->toContain('Head Office')
        ->and($headOffice->phone)->not->toBeNull();

    $regionOne = InsuranceOffice::where('region_code', '010000000')->firstOrFail();

    expect($regionOne->city)->toContain('Urdaneta')
        ->and(InsuranceOffice::count())->toBeGreaterThanOrEqual(17);
});

it('logs sent reminders for dedup', function () {
    $farmer = User::factory()->farmer()->create();
    $enrollment = insuranceEnrollment($farmer);

    InsuranceReminderLog::create([
        'user_id' => $farmer->id,
        'type' => ReminderType::RENEWAL,
        'reference_key' => "enrollment:{$enrollment->id}",
        'enrollment_id' => $enrollment->id,
        'sent_at' => now(),
    ]);

    expect(InsuranceReminderLog::where('user_id', $farmer->id)->count())->toBe(1);
});
