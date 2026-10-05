<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\CreditScoringConstants;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Domain\CreditScoring\Services\PdfGeneratorInterface;
use Closure;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Renders Farmer Trust Score snapshots into A4 PDFs via headless Chrome.
 *
 * Template is a plain PHP file (Blade is banned project-wide). Chrome and
 * Node module paths come from services.browsershot config so local, CI,
 * and production can each point at their own binaries.
 */
final class PdfGeneratorService implements PdfGeneratorInterface
{
    private const QR_CODE_SIZE_PX = 200;

    private const REPORT_ID_PAD_LENGTH = 6;

    private const TIER_GAUGE_COLORS = [
        'excellent' => '#4a8c42',
        'good' => '#6aa862',
        'fair' => '#d49a20',
        'developing' => '#b87e18',
        'new' => '#918880',
    ];

    /**
     * @param  Closure(string):string|null  $pdfRenderer  Overrides headless-Chrome rendering (used by tests).
     */
    public function __construct(
        private readonly ?Closure $pdfRenderer = null,
    ) {}

    public function generateCreditReport(CreditScoreSnapshot $snapshot): string
    {
        $html = $this->renderTemplate($snapshot);

        $path = "credit-reports/{$snapshot->user_id}/{$snapshot->id}.pdf";

        Storage::disk('local')->put($path, $this->renderer()->render($html));

        return $path;
    }

    private function renderer(): BrowsershotPdfRenderer
    {
        return new BrowsershotPdfRenderer($this->pdfRenderer);
    }

    private function renderTemplate(CreditScoreSnapshot $snapshot): string
    {
        $report = $this->buildReportData($snapshot);

        ob_start();

        include resource_path('pdf-templates/credit-report.php');

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(CreditScoreSnapshot $snapshot): array
    {
        $user = $snapshot->user;
        $scores = $snapshot->dimension_scores ?? [];
        $metrics = $snapshot->raw_metrics ?? [];
        $tier = $snapshot->tier instanceof ScoreTier ? $snapshot->tier : ScoreTier::fromScore((int) $snapshot->overall_score);

        $verifyUrl = rtrim((string) config('app.url'), '/').'/api/v1/verify-report/'.$snapshot->report_token;
        $qrSvg = QrCode::format('svg')->size(self::QR_CODE_SIZE_PX)->generate($verifyUrl);

        return [
            'farmer_name' => (string) $user->name,
            'farmer_email' => (string) $user->email,
            'member_since' => $user->created_at->format('F Y'),
            'report_id' => 'RPT-'.$snapshot->created_at->format('Y').'-'.str_pad((string) $snapshot->id, self::REPORT_ID_PAD_LENGTH, '0', STR_PAD_LEFT),
            'report_date' => $snapshot->created_at->format('F j, Y'),
            'expires_at' => $snapshot->report_expires_at?->format('F j, Y') ?? '—',
            'score' => (int) $snapshot->overall_score,
            'tier_label' => $tier->label(),
            'tier_description' => $tier->description(),
            'gauge_color' => self::TIER_GAUGE_COLORS[$tier->value] ?? self::TIER_GAUGE_COLORS['new'],
            'dimensions' => [
                ['label' => 'Plot Activity', 'score' => (int) ($scores['plot_activity'] ?? 0), 'weight_pct' => $this->weightPct(CreditScoringConstants::WEIGHT_PLOT_ACTIVITY)],
                ['label' => 'AI Recommendations', 'score' => (int) ($scores['recommendation_adherence'] ?? 0), 'weight_pct' => $this->weightPct(CreditScoringConstants::WEIGHT_RECOMMENDATION)],
                ['label' => 'Contract Fulfillment', 'score' => (int) ($scores['contract_fulfillment'] ?? 0), 'weight_pct' => $this->weightPct(CreditScoringConstants::WEIGHT_CONTRACT_FULFILLMENT)],
                ['label' => 'Offer Reliability', 'score' => (int) ($scores['offer_reliability'] ?? 0), 'weight_pct' => $this->weightPct(CreditScoringConstants::WEIGHT_OFFER_RELIABILITY)],
                ['label' => 'Transaction Volume', 'score' => (int) ($scores['transaction_volume'] ?? 0), 'weight_pct' => $this->weightPct(CreditScoringConstants::WEIGHT_TRANSACTION_VOLUME)],
                ['label' => 'Platform Tenure', 'score' => (int) ($scores['platform_tenure'] ?? 0), 'weight_pct' => $this->weightPct(CreditScoringConstants::WEIGHT_PLATFORM_TENURE)],
            ],
            'total_plots' => (int) ($metrics['active_plots'] ?? 0),
            'contracts_sold' => (int) ($metrics['sold_contracts'] ?? 0),
            'offers_completed' => (int) ($metrics['completed_offers'] ?? 0),
            'total_value_php' => '₱'.number_format((float) ($metrics['total_transaction_value'] ?? 0)),
            'account_age' => $this->formatAccountAge((int) ($metrics['account_age_months'] ?? 0), (int) ($metrics['account_age_days'] ?? 0)),
            'verified_label' => ! empty($metrics['email_verified']) ? 'Yes' : 'No',
            'qr_data_uri' => 'data:image/svg+xml;base64,'.base64_encode((string) $qrSvg),
            'verify_url' => $verifyUrl,
        ];
    }

    private function weightPct(float $weight): int
    {
        return (int) round($weight * 100);
    }

    private function formatAccountAge(int $months, int $days): string
    {
        if ($months >= 1) {
            return $months.' '.($months === 1 ? 'month' : 'months');
        }

        return $days.' '.($days === 1 ? 'day' : 'days');
    }
}
