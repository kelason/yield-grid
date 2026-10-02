<?php

declare(strict_types=1);

/**
 * Farmer Trust Score PDF report template.
 *
 * Plain PHP template (Blade is banned project-wide). Rendered via output
 * buffering by PdfGeneratorService and converted to PDF with Browsershot.
 * All CSS must be inline or in <style> (no external assets, no Tailwind).
 *
 * Provided variable:
 *
 * @var array{
 *   farmer_name: string,
 *   farmer_email: string,
 *   member_since: string,
 *   report_id: string,
 *   report_date: string,
 *   expires_at: string,
 *   score: int,
 *   tier_label: string,
 *   tier_description: string,
 *   gauge_color: string,
 *   dimensions: list<array{label: string, score: int, weight_pct: int}>,
 *   total_plots: int,
 *   contracts_sold: int,
 *   offers_completed: int,
 *   total_value_php: string,
 *   account_age: string,
 *   verified_label: string,
 *   qr_data_uri: string,
 *   verify_url: string,
 * } $report
 */
$e = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// SVG gauge geometry: r=54 → C ≈ 339.29
$gaugeCircumference = 2 * M_PI * 54;
$gaugeOffset = $gaugeCircumference * (1 - min(100, max(0, $report['score'])) / 100);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Farmer Trust Score Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1c1917; padding: 36px 40px; font-size: 13px; }
        .header { text-align: center; border-bottom: 3px solid #4a8c42; padding-bottom: 16px; margin-bottom: 20px; }
        .header h1 { color: #2d5829; font-size: 26px; letter-spacing: 0.5px; }
        .header .brand { font-size: 13px; color: #78716c; margin-top: 4px; }
        .header .confidential { display: inline-block; margin-top: 8px; font-size: 11px; font-weight: bold; color: #92400e; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 999px; padding: 2px 12px; }
        .meta { width: 100%; margin-bottom: 18px; }
        .meta td { padding: 3px 8px 3px 0; vertical-align: top; }
        .meta .label { color: #78716c; font-size: 11px; text-transform: uppercase; }
        .meta .value { font-weight: bold; }
        .score-wrap { text-align: center; margin: 10px 0 18px; }
        .tier-name { font-size: 20px; font-weight: bold; margin-top: 6px; }
        .tier-desc { color: #57534e; font-size: 12px; margin-top: 2px; }
        h2 { font-size: 14px; color: #2d5829; border-bottom: 2px solid #e7e5e4; padding-bottom: 6px; margin: 18px 0 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .dim { margin-bottom: 8px; }
        .dim-row { width: 100%; }
        .dim-row td { padding: 1px 0; font-size: 12px; }
        .dim-name { width: 42%; }
        .dim-bar { width: 40%; }
        .dim-score { width: 18%; text-align: right; font-weight: bold; }
        .bar-track { background: #e7e5e4; border-radius: 999px; height: 10px; }
        .bar-fill { border-radius: 999px; height: 10px; }
        .metrics { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .metrics td { border: 1px solid #e7e5e4; padding: 7px 10px; font-size: 12px; width: 50%; }
        .metrics .k { color: #78716c; }
        .metrics .v { font-weight: bold; }
        .verify { margin-top: 18px; border: 1px solid #e7e5e4; border-radius: 12px; padding: 12px 14px; }
        .verify table { width: 100%; }
        .verify td { vertical-align: middle; }
        .verify .qr { width: 110px; }
        .verify .url { font-size: 12px; color: #57534e; word-break: break-all; }
        .footer { margin-top: 16px; padding-top: 12px; border-top: 2px solid #e7e5e4; color: #a8a29e; font-size: 10px; text-align: center; }
        .footer p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FARMER TRUST SCORE REPORT</h1>
        <div class="brand">YieldGrid &bull; Buhay na Ani, Buhay na Kita</div>
        <div class="confidential">CONFIDENTIAL</div>
    </div>

    <table class="meta">
        <tr>
            <td><div class="label">Farmer</div><div class="value"><?= $e($report['farmer_name']) ?></div></td>
            <td><div class="label">Email</div><div class="value"><?= $e($report['farmer_email']) ?></div></td>
            <td><div class="label">Member Since</div><div class="value"><?= $e($report['member_since']) ?></div></td>
        </tr>
        <tr>
            <td><div class="label">Report ID</div><div class="value"><?= $e($report['report_id']) ?></div></td>
            <td><div class="label">Report Date</div><div class="value"><?= $e($report['report_date']) ?></div></td>
            <td><div class="label">Valid Until</div><div class="value"><?= $e($report['expires_at']) ?></div></td>
        </tr>
    </table>

    <div class="score-wrap">
        <svg width="150" height="150" viewBox="0 0 130 130">
            <circle cx="65" cy="65" r="54" fill="none" stroke="#e7e5e4" stroke-width="12" />
            <circle cx="65" cy="65" r="54" fill="none" stroke="<?= $e($report['gauge_color']) ?>" stroke-width="12"
                stroke-linecap="round" stroke-dasharray="<?= number_format($gaugeCircumference, 2) ?>"
                stroke-dashoffset="<?= number_format($gaugeOffset, 2) ?>" transform="rotate(-90 65 65)" />
            <text x="65" y="62" text-anchor="middle" font-size="30" font-weight="bold" fill="#1c1917"><?= $e((string) $report['score']) ?></text>
            <text x="65" y="80" text-anchor="middle" font-size="11" fill="#78716c">/ 100</text>
        </svg>
        <div class="tier-name" style="color: <?= $e($report['gauge_color']) ?>;"><?= $e($report['tier_label']) ?></div>
        <div class="tier-desc"><?= $e($report['tier_description']) ?></div>
    </div>

    <h2>Score Breakdown</h2>
    <?php foreach ($report['dimensions'] as $dimension) { ?>
        <div class="dim">
            <table class="dim-row">
                <tr>
                    <td class="dim-name"><?= $e($dimension['label']) ?> <span style="color:#a8a29e;">(<?= $e((string) $dimension['weight_pct']) ?>%)</span></td>
                    <td class="dim-bar"><div class="bar-track"><div class="bar-fill" style="width: <?= (int) $dimension['score'] ?>%; background: <?= $e($report['gauge_color']) ?>;"></div></div></td>
                    <td class="dim-score"><?= $e((string) $dimension['score']) ?>/100</td>
                </tr>
            </table>
        </div>
    <?php } ?>

    <h2>Key Metrics</h2>
    <table class="metrics">
        <tr>
            <td><span class="k">Total Plots:</span> <span class="v"><?= $e((string) $report['total_plots']) ?></span></td>
            <td><span class="k">Contracts Sold:</span> <span class="v"><?= $e((string) $report['contracts_sold']) ?></span></td>
        </tr>
        <tr>
            <td><span class="k">Offers Completed:</span> <span class="v"><?= $e((string) $report['offers_completed']) ?></span></td>
            <td><span class="k">Total Value:</span> <span class="v"><?= $e($report['total_value_php']) ?></span></td>
        </tr>
        <tr>
            <td><span class="k">Account Age:</span> <span class="v"><?= $e($report['account_age']) ?></span></td>
            <td><span class="k">Verified:</span> <span class="v"><?= $e($report['verified_label']) ?></span></td>
        </tr>
    </table>

    <div class="verify">
        <table>
            <tr>
                <td class="qr"><img src="<?= $e($report['qr_data_uri']) ?>" width="100" height="100" alt="Verification QR code"></td>
                <td>
                    <strong>Verify this report</strong><br>
                    <span class="url"><?= $e($report['verify_url']) ?></span><br>
                    <span class="url">Loan officers: scan the QR code to confirm authenticity.</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This report reflects on-platform data as of the report date and is valid until <?= $e($report['expires_at']) ?>.</p>
        <p>YieldGrid does not guarantee loan approval. Scores are internal indicators for microfinance evaluation only.</p>
    </div>
</body>
</html>
