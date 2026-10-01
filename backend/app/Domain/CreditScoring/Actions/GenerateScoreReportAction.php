<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Actions;

use App\Constants\CreditScoringConstants;
use App\Domain\CreditScoring\Enums\ReportStatus;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Jobs\GenerateScoreReportJob;
use Illuminate\Support\Str;

final class GenerateScoreReportAction
{
    public function execute(CreditScoreSnapshot $snapshot): void
    {
        $snapshot->update([
            'report_status' => ReportStatus::GENERATING,
            'report_token' => Str::random(CreditScoringConstants::REPORT_TOKEN_LENGTH),
            'report_expires_at' => now()->addDays(CreditScoringConstants::REPORT_EXPIRY_DAYS),
        ]);

        GenerateScoreReportJob::dispatch($snapshot->id);
    }
}
