<?php

// TEMPORARY PROBE — deleted after before/after measurement. Not committed.

use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use Database\Seeders\PlantingWindowSeeder;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('probe: counts queries for a 5-farmer reminder run', function () {
    Mail::fake();
    $this->seed(PlantingWindowSeeder::class);

    $regions = [null, '160000000', '010000000', null, '160000000'];

    foreach ($regions as $index => $region) {
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

        if ($region !== null) {
            UserAddress::create([
                'user_id' => $farmer->id,
                'label' => 'Home',
                'region_code' => $region,
                'city_municipality_code' => '013301',
                'barangay_code' => '013301001',
                'is_default' => true,
            ]);
        }

        $enrollment = InsuranceEnrollment::create([
            'user_id' => $farmer->id,
            'program' => InsuranceProgram::RICE,
            'season' => Season::WET,
            'season_year' => 2026,
            'status' => EnrollmentStatus::ACTIVE,
            'expires_at' => '2026-06-25 08:00:00',
        ]);

        if ($index === 0) {
            InsuranceClaim::create([
                'enrollment_id' => $enrollment->id,
                'loss_date' => '2026-06-01',
                'cause' => 'typhoon',
            ]);
        }
    }

    $this->travelTo('2026-06-10 08:00:00');

    DB::enableQueryLog();
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $count = count(DB::getQueryLog());
    $this->travelBack();

    fwrite(STDERR, "\nPROBE_QUERY_COUNT: {$count}\n");

    expect($count)->toBeGreaterThan(0);
});
