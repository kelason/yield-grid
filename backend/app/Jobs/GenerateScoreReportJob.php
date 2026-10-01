<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\CreditScoringConstants;
use App\Domain\CreditScoring\Enums\ReportStatus;
use App\Domain\CreditScoring\Events\ScoreReportGenerated;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Domain\CreditScoring\Services\PdfGeneratorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateScoreReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = CreditScoringConstants::REPORT_GENERATION_TIMEOUT_SECONDS;

    public function __construct(
        private readonly int $snapshotId,
    ) {}

    public function handle(PdfGeneratorInterface $pdfService): void
    {
        $snapshot = CreditScoreSnapshot::with('user')->findOrFail($this->snapshotId);

        try {
            $pdfPath = $pdfService->generateCreditReport($snapshot);

            $snapshot->update([
                'report_status' => ReportStatus::READY,
                'report_path' => $pdfPath,
                'report_generated_at' => now(),
            ]);

            ScoreReportGenerated::dispatch($snapshot);
        } catch (\Throwable $e) {
            $snapshot->update(['report_status' => ReportStatus::FAILED]);

            throw $e;
        }
    }
}
