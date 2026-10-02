<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Services;

use App\Domain\CreditScoring\Models\CreditScoreSnapshot;

interface PdfGeneratorInterface
{
    /**
     * Render a credit score snapshot into a PDF report.
     *
     * @return string Storage-relative path, e.g. "credit-reports/1/5.pdf".
     */
    public function generateCreditReport(CreditScoreSnapshot $snapshot): string;
}
