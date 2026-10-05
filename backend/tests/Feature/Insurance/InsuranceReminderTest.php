<?php

use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\ReminderType;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceReminderLog;
use App\Domain\Insurance\Models\PlantingWindow;
use App\Insurance\Mail\InsuranceReminderMail;
use Database\Seeders\InsuranceOfficeSeeder;
use Database\Seeders\PlantingWindowSeeder;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function reminderFarmer(?string $regionCode = null): User
{
    $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

    if ($regionCode !== null) {
        UserAddress::create([
            'user_id' => $farmer->id,
            'label' => 'Home',
            'region_code' => $regionCode,
            'city_municipality_code' => '013301',
            'barangay_code' => '013301001',
            'is_default' => true,
        ]);
    }

    return $farmer;
}

function reminderEnrollment(User $farmer, array $overrides = []): InsuranceEnrollment
{
    return InsuranceEnrollment::create(array_merge([
        'user_id' => $farmer->id,
        'program' => InsuranceProgram::RICE,
        'season' => Season::WET,
        'season_year' => 2026,
    ], $overrides));
}

it('sends enrollment-window reminders as the season approaches', function () {
    Mail::fake();
    $this->seed(PlantingWindowSeeder::class);
    reminderFarmer('010000000');

    $this->travelTo('2026-04-10 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 2);

    $keys = InsuranceReminderLog::where('type', ReminderType::ENROLLMENT_WINDOW)
        ->orderBy('reference_key')
        ->pluck('reference_key')
        ->all();

    expect($keys)->toBe(['window:corn:wet:2026', 'window:rice:wet:2026']);
});

it('keeps each program window when both share a season', function () {
    Mail::fake();
    $this->seed(PlantingWindowSeeder::class);

    PlantingWindow::where('region_code', 'NATIONAL')
        ->where('program', InsuranceProgram::CORN)
        ->where('season', Season::WET)
        ->update([
            'window_start_month' => 6,
            'window_start_day' => 1,
            'window_end_month' => 8,
            'window_end_day' => 15,
        ]);

    reminderFarmer();

    $this->travelTo('2026-05-15 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 2);

    $logs = InsuranceReminderLog::where('type', ReminderType::ENROLLMENT_WINDOW)
        ->orderBy('reference_key')
        ->get();

    expect($logs->pluck('reference_key')->all())->toBe(['window:corn:wet:2026', 'window:rice:wet:2026'])
        ->and($logs->map(fn ($log) => $log->meta['window_label'])->all())->toBe(['Jun 1 – Aug 15', 'May 1 – Jul 31']);

    foreach (['rice', 'corn'] as $program) {
        Mail::assertQueued(
            InsuranceReminderMail::class,
            fn (InsuranceReminderMail $mail) => ($mail->meta['program'] ?? null) === $program
        );
    }
});

it('does not resend the same seasonal reminder twice', function () {
    Mail::fake();
    $this->seed(PlantingWindowSeeder::class);
    reminderFarmer();

    $this->travelTo('2026-04-10 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 2);
    expect(InsuranceReminderLog::count())->toBe(2);
});

it('reminds during the dry-season tail spanning the new year', function () {
    Mail::fake();
    $this->seed(PlantingWindowSeeder::class);
    reminderFarmer();

    $this->travelTo('2027-01-15 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 2);
    expect(InsuranceReminderLog::where('reference_key', 'window:rice:dry:2026')->count())->toBe(1)
        ->and(InsuranceReminderLog::where('reference_key', 'window:corn:dry:2026')->count())->toBe(1);
});

it('retries later when mail dispatch fails instead of aborting the run', function () {
    Mail::shouldReceive('to')->twice()->andThrow(new RuntimeException('smtp down'));
    $this->seed(PlantingWindowSeeder::class);
    reminderFarmer();

    $this->travelTo('2026-04-10 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    expect(InsuranceReminderLog::count())->toBe(0);
});

it('stays silent far outside any planting window', function () {
    Mail::fake();
    $this->seed(PlantingWindowSeeder::class);
    reminderFarmer();

    $this->travelTo('2026-02-10 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertNothingQueued();
    expect(InsuranceReminderLog::count())->toBe(0);
});

it('sends notice-of-loss deadline reminders for unfiled claims', function () {
    Mail::fake();
    $farmer = reminderFarmer();
    $enrollment = reminderEnrollment($farmer);

    $this->travelTo('2026-02-10 08:00:00');
    InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(8)->toDateString(),
        'cause' => 'typhoon',
    ]);
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 1);
    expect(InsuranceReminderLog::where('type', ReminderType::NOTICE_OF_LOSS_DEADLINE)->count())->toBe(1);
});

it('skips deadline reminders once the notice of loss is filed', function () {
    Mail::fake();
    $farmer = reminderFarmer();
    $enrollment = reminderEnrollment($farmer);
    InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(8)->toDateString(),
        'cause' => 'typhoon',
        'status' => ClaimStatus::NOTICE_OF_LOSS_FILED,
        'notice_of_loss_filed_at' => now()->subDay(),
    ]);

    $this->travelTo('2026-02-10 08:00:00');
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertNothingQueued();
});

it('sends renewal reminders for expiring active enrollments', function () {
    Mail::fake();
    $farmer = reminderFarmer();

    $this->travelTo('2026-02-10 08:00:00');
    reminderEnrollment($farmer, [
        'status' => EnrollmentStatus::ACTIVE,
        'expires_at' => now()->addDays(20),
    ]);
    reminderEnrollment($farmer, [
        'status' => EnrollmentStatus::ACTIVE,
        'season' => Season::DRY,
        'expires_at' => now()->addDays(90),
    ]);
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 1);
    expect(InsuranceReminderLog::where('type', ReminderType::RENEWAL)->count())->toBe(1);
});

it('sends followups for idle in-progress claims', function () {
    Mail::fake();
    $farmer = reminderFarmer();
    $enrollment = reminderEnrollment($farmer);

    $this->travelTo('2026-02-10 08:00:00');
    $idle = InsuranceClaim::create([
        'enrollment_id' => $enrollment->id,
        'loss_date' => now()->subDays(40)->toDateString(),
        'cause' => 'flood',
        'status' => ClaimStatus::FIELD_INSPECTION,
        'notice_of_loss_filed_at' => now()->subDays(30),
    ]);
    InsuranceClaim::where('id', $idle->id)->update(['updated_at' => now()->subDays(20)]);
    $this->artisan('insurance:send-reminders')->assertSuccessful();
    $this->travelBack();

    Mail::assertQueued(InsuranceReminderMail::class, 1);
    expect(InsuranceReminderLog::where('type', ReminderType::CLAIM_FOLLOWUP)->count())->toBe(1);
});

it('serves recent reminders with translation keys', function () {
    $farmer = reminderFarmer();
    Sanctum::actingAs($farmer, ['*']);

    InsuranceReminderLog::create([
        'user_id' => $farmer->id,
        'type' => ReminderType::ENROLLMENT_WINDOW,
        'reference_key' => 'window:rice:wet:2026',
        'meta' => ['program' => 'rice', 'season' => 'wet', 'season_year' => 2026],
        'sent_at' => now(),
    ]);
    InsuranceReminderLog::create([
        'user_id' => User::factory()->farmer()->create()->id,
        'type' => ReminderType::RENEWAL,
        'reference_key' => 'enrollment:99',
        'sent_at' => now(),
    ]);

    $this->getJson('/api/v1/farmer/insurance/reminders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'enrollment_window')
        ->assertJsonPath('data.0.title_key', 'insurance.reminders.enrollment_window.title')
        ->assertJsonPath('data.0.message_key', 'insurance.reminders.enrollment_window.message');
});

it('serves the office directory with the serving region flagged', function () {
    $this->seed(InsuranceOfficeSeeder::class);

    $farmer = reminderFarmer('010000000');
    Sanctum::actingAs($farmer, ['*']);

    $response = $this->getJson('/api/v1/farmer/insurance/offices')->assertOk();

    $offices = collect($response->json('data'));
    $serving = $offices->firstWhere('region_code', '010000000');

    expect($serving['is_serving_region'])->toBeTrue()
        ->and($serving['city'])->toContain('Urdaneta')
        ->and($offices->firstWhere('region_code', null)['name'])->toContain('Head Office')
        ->and($offices->count())->toBeGreaterThanOrEqual(17);
});
