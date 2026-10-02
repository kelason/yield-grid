<?php

use App\Domain\CreditScoring\Actions\CalculateCreditScoreAction;
use App\Domain\CreditScoring\Enums\ReportStatus;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Jobs\GenerateScoreReportJob;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function creditEndpointSnapshot(User $farmer, array $overrides = []): CreditScoreSnapshot
{
    return CreditScoreSnapshot::create(array_merge([
        'user_id' => $farmer->id,
        'overall_score' => 78,
        'tier' => ScoreTier::GOOD,
        'dimension_scores' => [
            'plot_activity' => 80,
            'recommendation_adherence' => 65,
            'contract_fulfillment' => 95,
            'offer_reliability' => 75,
            'transaction_volume' => 60,
            'platform_tenure' => 50,
        ],
        'raw_metrics' => ['active_plots' => 4],
        'report_status' => ReportStatus::NONE,
    ], $overrides));
}

it('computes the score on first view when no snapshot exists', function () {
    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    $response = $this->getJson('/api/v1/farmer/credit-score');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'overall_score',
                'tier',
                'tier_label',
                'dimension_scores',
                'improvement_tips',
                'report_status',
            ],
        ]);

    expect(CreditScoreSnapshot::where('user_id', $farmer->id)->count())->toBe(1);
});

it('serves the latest snapshot without recomputing', function () {
    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    creditEndpointSnapshot($farmer, ['overall_score' => 40, 'created_at' => now()->subDay()]);
    creditEndpointSnapshot($farmer, ['overall_score' => 78]);

    $response = $this->getJson('/api/v1/farmer/credit-score');

    $response->assertOk()->assertJsonPath('data.overall_score', 78);

    expect(CreditScoreSnapshot::where('user_id', $farmer->id)->count())->toBe(2);
});

it('regenerates improvement tips when serving a stored snapshot', function () {
    $farmer = User::factory()->farmer()->unverified()->create(['phone' => null]);
    Sanctum::actingAs($farmer, ['*']);

    app(CalculateCreditScoreAction::class)->execute($farmer);

    $response = $this->getJson('/api/v1/farmer/credit-score');

    $response->assertOk();

    $tips = $response->json('data.improvement_tips');

    expect($tips)->not->toBeEmpty();
    expect(array_column($tips, 'dimension'))->toContain('plot_activity');
    expect(array_keys($tips[0]))->toContain('dimension')->toContain('message');
});

it('returns score history newest first', function () {
    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    creditEndpointSnapshot($farmer, ['overall_score' => 40, 'created_at' => now()->subDays(2)]);
    creditEndpointSnapshot($farmer, ['overall_score' => 60, 'created_at' => now()->subDay()]);
    creditEndpointSnapshot($farmer, ['overall_score' => 78]);

    $response = $this->getJson('/api/v1/farmer/credit-score/history');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.overall_score', 78)
        ->assertJsonPath('data.2.overall_score', 40);
});

it('forbids buyers from credit score routes', function () {
    $buyer = User::factory()->buyer()->create();
    Sanctum::actingAs($buyer, ['*']);

    $this->getJson('/api/v1/farmer/credit-score')->assertForbidden();
    $this->getJson('/api/v1/farmer/credit-score/history')->assertForbidden();
    $this->postJson('/api/v1/farmer/credit-score/report')->assertForbidden();
});

it('requires authentication for credit score routes', function () {
    $this->getJson('/api/v1/farmer/credit-score')->assertUnauthorized();
    $this->postJson('/api/v1/farmer/credit-score/report')->assertUnauthorized();
});

it('starts report generation for a verified farmer', function () {
    Queue::fake();

    $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($farmer, ['*']);
    $snapshot = creditEndpointSnapshot($farmer);

    $response = $this->postJson('/api/v1/farmer/credit-score/report');

    $response->assertAccepted()->assertJsonPath('message', 'Report generation started.');

    Queue::assertPushed(GenerateScoreReportJob::class);

    $snapshot->refresh();

    expect($snapshot->report_status)->toBe(ReportStatus::GENERATING)
        ->and($snapshot->report_token)->toHaveLength(64)
        ->and($snapshot->report_expires_at)->not->toBeNull();
});

it('forbids report generation for unverified farmers', function () {
    $farmer = User::factory()->farmer()->unverified()->create();
    Sanctum::actingAs($farmer, ['*']);
    creditEndpointSnapshot($farmer);

    $this->postJson('/api/v1/farmer/credit-score/report')->assertForbidden();
});

it('returns 404 when generating a report with no snapshot', function () {
    $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($farmer, ['*']);

    $this->postJson('/api/v1/farmer/credit-score/report')->assertNotFound();
});

it('downloads a ready report for the owning farmer', function () {
    Storage::fake('local');

    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    $token = Str::random(64);
    $path = "credit-reports/{$farmer->id}/1.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    creditEndpointSnapshot($farmer, [
        'report_status' => ReportStatus::READY,
        'report_path' => $path,
        'report_token' => $token,
        'report_generated_at' => now(),
        'report_expires_at' => now()->addDays(30),
    ]);

    $response = $this->get("/api/v1/farmer/credit-score/report/{$token}/download");

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('YieldGrid-Trust-Score-Report.pdf');
});

it('returns 410 when downloading an expired report', function () {
    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    $token = Str::random(64);
    creditEndpointSnapshot($farmer, [
        'report_status' => ReportStatus::READY,
        'report_path' => 'credit-reports/expired.pdf',
        'report_token' => $token,
        'report_expires_at' => now()->subDay(),
    ]);

    $this->get("/api/v1/farmer/credit-score/report/{$token}/download")->assertGone();
});

it('forbids downloading another farmer report', function () {
    $owner = User::factory()->farmer()->create();
    $intruder = User::factory()->farmer()->create();
    Sanctum::actingAs($intruder, ['*']);

    $token = Str::random(64);
    creditEndpointSnapshot($owner, [
        'report_status' => ReportStatus::READY,
        'report_path' => 'credit-reports/owner.pdf',
        'report_token' => $token,
        'report_expires_at' => now()->addDays(30),
    ]);

    $this->get("/api/v1/farmer/credit-score/report/{$token}/download")->assertForbidden();
});

it('returns 404 when downloading with an unknown token', function () {
    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    $this->get('/api/v1/farmer/credit-score/report/'.Str::random(64).'/download')->assertNotFound();
});

it('verifies a valid report publicly without authentication', function () {
    $farmer = User::factory()->farmer()->create(['name' => 'Juan Dela Cruz']);
    $token = Str::random(64);
    creditEndpointSnapshot($farmer, [
        'overall_score' => 78,
        'tier' => ScoreTier::GOOD,
        'report_status' => ReportStatus::READY,
        'report_token' => $token,
        'report_generated_at' => now(),
        'report_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson("/api/v1/verify-report/{$token}");

    $response->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('farmer_name', 'Juan Dela Cruz')
        ->assertJsonPath('score', 78)
        ->assertJsonPath('tier', ScoreTier::GOOD->label());
});

it('rejects expired and unknown reports on the public verify endpoint', function () {
    $farmer = User::factory()->farmer()->create();
    $expiredToken = Str::random(64);
    creditEndpointSnapshot($farmer, [
        'report_status' => ReportStatus::READY,
        'report_token' => $expiredToken,
        'report_expires_at' => now()->subDay(),
    ]);

    $this->getJson("/api/v1/verify-report/{$expiredToken}")
        ->assertNotFound()
        ->assertJsonPath('valid', false);

    $this->getJson('/api/v1/verify-report/'.Str::random(64))
        ->assertNotFound()
        ->assertJsonPath('valid', false);
});

it('rate limits score views', function () {
    $farmer = User::factory()->farmer()->create();
    Sanctum::actingAs($farmer, ['*']);

    for ($i = 0; $i < 10; $i++) {
        $this->getJson('/api/v1/farmer/credit-score')->assertOk();
    }

    $this->getJson('/api/v1/farmer/credit-score')->assertStatus(429);
});
