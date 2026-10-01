<?php

use App\Domain\CreditScoring\Enums\ReportStatus;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Infrastructure\Services\PdfGeneratorService;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function creditPdfSnapshot(User $farmer): CreditScoreSnapshot
{
    return CreditScoreSnapshot::create([
        'user_id' => $farmer->id,
        'overall_score' => 78,
        'tier' => ScoreTier::GOOD,
        'dimension_scores' => ['plot_activity' => 80],
        'raw_metrics' => ['active_plots' => 4],
        'report_status' => ReportStatus::GENERATING,
        'report_token' => Str::random(64),
        'report_expires_at' => now()->addDays(30),
    ]);
}

it('stores the generated report on the local disk the download endpoint reads', function () {
    Storage::fake('local');

    $farmer = User::factory()->farmer()->create();
    $snapshot = creditPdfSnapshot($farmer);

    $service = new PdfGeneratorService(pdfRenderer: fn (string $html): string => '%PDF-1.4 fake');
    $path = $service->generateCreditReport($snapshot);

    expect($path)->toBe("credit-reports/{$farmer->id}/{$snapshot->id}.pdf");
    Storage::disk('local')->assertExists($path);
    expect(Storage::disk('local')->fileSize($path))->toBeGreaterThan(0);
});
