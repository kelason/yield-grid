<?php

use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Events\EnrollmentPackGenerated;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Services\EnrollmentPackGeneratorInterface;
use App\Infrastructure\Insurance\Services\EnrollmentPackGeneratorService;
use App\Jobs\GenerateEnrollmentPackJob;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function packPlot(User $farmer): Plot
{
    $farm = Farm::create([
        'user_id' => $farmer->id,
        'name' => 'Test Farm',
        'city' => 'Muñoz',
        'state' => 'Nueva Ecija',
        'country' => 'Philippines',
    ]);

    return Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Test Plot',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 1.5,
    ]);
}

function packEnrollment(User $farmer, array $overrides = []): InsuranceEnrollment
{
    return InsuranceEnrollment::create(array_merge([
        'user_id' => $farmer->id,
        'plot_id' => packPlot($farmer)->id,
        'program' => InsuranceProgram::RICE,
        'season' => Season::WET,
        'season_year' => 2026,
    ], $overrides));
}

function packFarmer(): User
{
    $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($farmer, ['*']);

    return $farmer;
}

it('queues pack generation and marks the enrollment generating', function () {
    Queue::fake([GenerateEnrollmentPackJob::class]);

    $farmer = packFarmer();
    $enrollment = packEnrollment($farmer);

    $this->postJson("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/pack")
        ->assertAccepted();

    Queue::assertPushed(GenerateEnrollmentPackJob::class);

    $fresh = $enrollment->fresh();

    expect($fresh->pack_status)->toBe(PackStatus::GENERATING)
        ->and($fresh->pack_token)->not->toBeNull()
        ->and($fresh->pack_expires_at)->not->toBeNull();
});

it('forbids requesting a pack for another farmer enrollment', function () {
    packFarmer();
    $other = packEnrollment(User::factory()->farmer()->create());

    $this->postJson("/api/v1/farmer/insurance/enrollments/{$other->id}/pack")->assertForbidden();
});

it('marks the pack ready when generation succeeds', function () {
    Event::fake([EnrollmentPackGenerated::class]);

    $farmer = User::factory()->farmer()->create();
    $enrollment = packEnrollment($farmer, ['pack_status' => PackStatus::GENERATING]);

    $this->mock(EnrollmentPackGeneratorInterface::class, function ($mock) use ($enrollment) {
        $mock->shouldReceive('generateEnrollmentPack')
            ->once()
            ->withArgs(fn (InsuranceEnrollment $arg): bool => $arg->id === $enrollment->id)
            ->andReturn("enrollment-packs/{$enrollment->user_id}/{$enrollment->id}.pdf");
    });

    GenerateEnrollmentPackJob::dispatchSync($enrollment->id);

    $enrollment->refresh();

    expect($enrollment->pack_status)->toBe(PackStatus::READY)
        ->and($enrollment->pack_path)->toBe("enrollment-packs/{$enrollment->user_id}/{$enrollment->id}.pdf")
        ->and($enrollment->pack_generated_at)->not->toBeNull();

    Event::assertDispatched(EnrollmentPackGenerated::class);
});

it('marks the pack failed when generation throws', function () {
    $farmer = User::factory()->farmer()->create();
    $enrollment = packEnrollment($farmer, ['pack_status' => PackStatus::GENERATING]);

    $this->mock(EnrollmentPackGeneratorInterface::class, function ($mock) {
        $mock->shouldReceive('generateEnrollmentPack')->once()->andThrow(new RuntimeException('chrome down'));
    });

    try {
        GenerateEnrollmentPackJob::dispatchSync($enrollment->id);
        $this->fail('Expected the job to rethrow.');
    } catch (RuntimeException) {
        expect($enrollment->fresh()->pack_status)->toBe(PackStatus::FAILED);
    }
});

it('stores the generated pack on the local disk the download endpoint reads', function () {
    Storage::fake('local');

    $farmer = User::factory()->farmer()->create();
    $enrollment = packEnrollment($farmer);

    $service = new EnrollmentPackGeneratorService(pdfRenderer: fn (string $html): string => '%PDF-1.4 fake');
    $path = $service->generateEnrollmentPack($enrollment);

    expect($path)->toBe("enrollment-packs/{$farmer->id}/{$enrollment->id}.pdf");
    Storage::disk('local')->assertExists($path);
    expect(Storage::disk('local')->fileSize($path))->toBeGreaterThan(0);
});

it('downloads a ready pack for the owner', function () {
    Storage::fake('local');

    $farmer = packFarmer();
    $path = "enrollment-packs/{$farmer->id}/1.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 fake');

    $enrollment = packEnrollment($farmer, [
        'pack_status' => PackStatus::READY,
        'pack_path' => $path,
        'pack_expires_at' => now()->addDays(30),
    ]);

    $this->get("/api/v1/farmer/insurance/enrollments/{$enrollment->id}/pack/download")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('throttles pack downloads to three per day', function () {
    Storage::fake('local');

    $farmer = packFarmer();
    $path = "enrollment-packs/{$farmer->id}/1.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 fake');

    $enrollment = packEnrollment($farmer, [
        'pack_status' => PackStatus::READY,
        'pack_path' => $path,
        'pack_expires_at' => now()->addDays(30),
    ]);

    $url = "/api/v1/farmer/insurance/enrollments/{$enrollment->id}/pack/download";

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();
    $this->get($url)->assertOk();
    $this->get($url)->assertTooManyRequests();
});

it('exposes the throttle retry hint to browsers on the fourth download', function () {
    Storage::fake('local');

    $farmer = packFarmer();
    $path = "enrollment-packs/{$farmer->id}/1.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 fake');

    $enrollment = packEnrollment($farmer, [
        'pack_status' => PackStatus::READY,
        'pack_path' => $path,
        'pack_expires_at' => now()->addDays(30),
    ]);

    $url = "/api/v1/farmer/insurance/enrollments/{$enrollment->id}/pack/download";

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();
    $this->get($url)->assertOk();
    $this->get($url, ['Origin' => 'http://localhost:5173'])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertHeader('Access-Control-Expose-Headers', 'Retry-After');
});

it('rejects downloading expired and unready packs', function () {
    $farmer = packFarmer();

    $expired = packEnrollment($farmer, [
        'pack_status' => PackStatus::READY,
        'pack_path' => 'enrollment-packs/old.pdf',
        'pack_expires_at' => now()->subDay(),
    ]);

    $this->get("/api/v1/farmer/insurance/enrollments/{$expired->id}/pack/download")
        ->assertStatus(410);

    $generating = packEnrollment($farmer, ['pack_status' => PackStatus::GENERATING]);

    $this->get("/api/v1/farmer/insurance/enrollments/{$generating->id}/pack/download")
        ->assertStatus(409);

    $other = packEnrollment(User::factory()->farmer()->create(), [
        'pack_status' => PackStatus::READY,
        'pack_path' => 'enrollment-packs/other.pdf',
        'pack_expires_at' => now()->addDays(30),
    ]);

    $this->get("/api/v1/farmer/insurance/enrollments/{$other->id}/pack/download")
        ->assertForbidden();
});
