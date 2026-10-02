<?php

use App\Domain\CreditScoring\Enums\ReportStatus;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Events\ScoreReportGenerated;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Domain\CreditScoring\Services\PdfGeneratorInterface;
use App\Infrastructure\Services\PdfGeneratorService;
use App\Jobs\GenerateScoreReportJob;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function creditJobSnapshot(User $farmer): CreditScoreSnapshot
{
    return CreditScoreSnapshot::create([
        'user_id' => $farmer->id,
        'overall_score' => 78,
        'tier' => ScoreTier::GOOD,
        'dimension_scores' => ['plot_activity' => 80],
        'raw_metrics' => ['active_plots' => 4],
        'report_status' => ReportStatus::GENERATING,
        'report_token' => str_repeat('a', 64),
        'report_expires_at' => now()->addDays(30),
    ]);
}

it('marks the snapshot ready when PDF generation succeeds', function () {
    Event::fake([ScoreReportGenerated::class]);

    $farmer = User::factory()->farmer()->create();
    $snapshot = creditJobSnapshot($farmer);

    $this->mock(PdfGeneratorInterface::class, function ($mock) use ($snapshot) {
        $mock->shouldReceive('generateCreditReport')
            ->once()
            ->withArgs(fn (CreditScoreSnapshot $arg): bool => $arg->id === $snapshot->id)
            ->andReturn("credit-reports/{$snapshot->user_id}/{$snapshot->id}.pdf");
    });

    GenerateScoreReportJob::dispatchSync($snapshot->id);

    $snapshot->refresh();

    expect($snapshot->report_status)->toBe(ReportStatus::READY)
        ->and($snapshot->report_path)->toBe("credit-reports/{$snapshot->user_id}/{$snapshot->id}.pdf")
        ->and($snapshot->report_generated_at)->not->toBeNull();

    Event::assertDispatched(ScoreReportGenerated::class);
});

it('marks the snapshot failed when PDF generation throws', function () {
    $farmer = User::factory()->farmer()->create();
    $snapshot = creditJobSnapshot($farmer);

    $this->mock(PdfGeneratorInterface::class, function ($mock) {
        $mock->shouldReceive('generateCreditReport')->once()->andThrow(new RuntimeException('chrome crashed'));
    });

    try {
        GenerateScoreReportJob::dispatchSync($snapshot->id);

        $this->fail('Expected the job to rethrow the PDF exception.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('chrome crashed');
    }

    expect($snapshot->refresh()->report_status)->toBe(ReportStatus::FAILED);
});

it('resolves the real PDF generator binding the queue worker needs', function () {
    // Guards against "Target [PdfGeneratorInterface] is not instantiable" in
    // workers: the tests above mock the interface, so only this one exercises
    // the AppServiceProvider binding through a real container resolution.
    expect(app(PdfGeneratorInterface::class))->toBeInstanceOf(PdfGeneratorService::class);
});
