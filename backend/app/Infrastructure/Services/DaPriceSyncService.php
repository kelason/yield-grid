<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\DTOs\DaPriceSyncResult;
use App\Domain\Marketplace\Models\CropPriceSyncRun;
use App\Domain\Marketplace\Repositories\CropReferencePriceRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Daily DA price sync: POSTs {commodity, region} to the Bantay Presyo
 * table endpoints, parses the returned rows, and upserts regional plus
 * national-average reference rows. Never throws — every request is
 * isolated, failures are recorded as sync runs, and the API keeps
 * serving last-good data.
 */
final class DaPriceSyncService
{
    private const NATIONAL_MARKET_LABEL = 'DA national average';

    public function __construct(
        private readonly CropReferencePriceRepositoryInterface $prices,
        private readonly AiPriceExtractorService $aiExtractor,
    ) {}

    public function sync(): DaPriceSyncResult
    {
        $startedAt = now();

        if (! (bool) config('services.da_prices.enabled', true)) {
            $this->recordRun(CropPriceSyncRun::STATUS_DISABLED, 0, 'DA price sync is disabled.', $startedAt);

            return new DaPriceSyncResult(CropPriceSyncRun::STATUS_DISABLED, 0, 'DA price sync is disabled.');
        }

        $baseUrl = rtrim((string) config('services.da_prices.base_url', ''), '/');
        $endpoints = config('services.da_prices.endpoints', []);
        $regions = config('services.da_prices.regions', []);

        if ($baseUrl === '' || $endpoints === [] || $regions === []) {
            $this->recordRun(CropPriceSyncRun::STATUS_FAILED, 0, 'DA price sync is not configured.', $startedAt);

            return new DaPriceSyncResult(CropPriceSyncRun::STATUS_FAILED, 0, 'DA price sync is not configured.');
        }

        $timeout = (int) config('services.da_prices.timeout', MarketplaceConstants::PRICE_GUIDE_SYNC_REQUEST_TIMEOUT_SECONDS);
        $maxAttempts = max(1, (int) config('services.da_prices.max_attempts', MarketplaceConstants::PRICE_GUIDE_SYNC_MAX_ATTEMPTS));
        $rows = [];
        $rawHtml = '';
        $reached = 0;
        $errors = [];

        foreach ($endpoints as $endpoint) {
            foreach ((array) ($endpoint['commodities'] ?? []) as $commodity) {
                foreach ($regions as $region) {
                    $fetched = $this->fetchTable($baseUrl, (string) $endpoint['page'], $commodity, (string) $region, $timeout, $maxAttempts);

                    if ($fetched === null) {
                        $errors[] = "{$endpoint['page']}/{$commodity}/{$region}";

                        continue;
                    }

                    $reached++;
                    $rawHtml .= "\n".mb_substr($fetched, 0, 2000);

                    foreach (DaPriceTableParser::parse(null, $fetched, (string) $region) as $row) {
                        $rows[] = $row;
                    }
                }
            }
        }

        $usedAi = false;

        if ($rows === [] && $reached > 0 && (bool) config('services.da_prices.ai_fallback', true)) {
            $rows = $this->aiExtractor->extract($rawHtml);
            $usedAi = true;
        }

        if ($rows === []) {
            if ($reached > 0) {
                $this->recordRun(CropPriceSyncRun::STATUS_PARTIAL, 0, 'DA responded but no price rows parsed.', $startedAt);

                return new DaPriceSyncResult(CropPriceSyncRun::STATUS_PARTIAL, 0, 'No price rows parsed.', $usedAi);
            }

            $detail = $errors === [] ? 'No DA endpoints reached.' : 'Unreachable: '.implode(', ', array_slice($errors, 0, 5));
            $this->recordRun(CropPriceSyncRun::STATUS_FAILED, 0, $detail, $startedAt);

            return new DaPriceSyncResult(CropPriceSyncRun::STATUS_FAILED, 0, $detail);
        }

        $rows = array_merge($rows, self::baseRollups($rows));
        $rows = array_merge($rows, self::nationalAverages($rows));
        $count = $this->prices->upsertRows($rows);
        $this->recordRun(CropPriceSyncRun::STATUS_SUCCESS, $count, null, $startedAt);

        Log::info('DA price sync completed', ['rows' => $count, 'used_ai' => $usedAi, 'errors' => count($errors)]);

        return new DaPriceSyncResult(CropPriceSyncRun::STATUS_SUCCESS, $count, null, $usedAi);
    }

    /**
     * The DA backend hangs intermittently, so each table is attempted up to
     * $maxAttempts times before the sync moves on and the guide falls back
     * to YieldGrid and AI data. Each hanging attempt already waits out the
     * full timeout, which spaces the retries without extra sleeps.
     */
    private function fetchTable(
        string $baseUrl,
        string $page,
        mixed $commodity,
        string $region,
        int $timeout,
        int $maxAttempts,
    ): ?string {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout($timeout)
                    ->asForm()
                    ->post("{$baseUrl}/tbl_price_get_comm_price_{$page}.php", [
                        'commodity' => $commodity,
                        'region' => $region,
                    ]);

                if ($response->successful()) {
                    return $response->body();
                }

                Log::debug('DA price endpoint retrying on HTTP error', [
                    'page' => $page,
                    'commodity' => $commodity,
                    'region' => $region,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                ]);
            } catch (\Throwable $e) {
                Log::debug('DA price endpoint retrying after failure', [
                    'page' => $page,
                    'commodity' => $commodity,
                    'region' => $region,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('DA price endpoint failed', [
            'page' => $page,
            'commodity' => $commodity,
            'region' => $region,
            'attempts' => $maxAttempts,
        ]);

        return null;
    }

    /**
     * @param  list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>  $rows
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    private static function baseRollups(array $rows): array
    {
        /** @var array<string, array{prices: list<float>, observed: string, regions: array<string, bool>}> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            $base = DaCropVocabulary::canonicalSlug($row['crop_slug']);

            if ($base === $row['crop_slug']) {
                continue;
            }

            $key = $base.'|'.$row['tier'].'|'.($row['region_code'] ?? '');
            $grouped[$key] ??= [
                'slug' => $base,
                'tier' => $row['tier'],
                'source' => $row['source'],
                'region' => $row['region_code'],
                'observed' => $row['observed_at'],
                'prices' => [],
                'variants' => 0,
            ];

            $grouped[$key]['prices'][] = $row['price_per_kg'];
            $grouped[$key]['variants']++;
            $grouped[$key]['observed'] = max($grouped[$key]['observed'], $row['observed_at']);
        }

        $rollups = [];

        foreach ($grouped as $group) {
            $rollups[] = [
                'crop_slug' => $group['slug'],
                'crop_display_name' => str($group['slug'])->headline()->toString(),
                'tier' => $group['tier'],
                'price_per_kg' => round(array_sum($group['prices']) / count($group['prices']), 2),
                'currency' => 'PHP',
                'region_code' => $group['region'],
                'market_name' => "DA monitored average ({$group['variants']} variants)",
                'source' => $group['source'],
                'observed_at' => $group['observed'],
            ];
        }

        return $rollups;
    }

    /**
     * @param  list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>  $rows
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    private static function nationalAverages(array $rows): array
    {
        /** @var array<string, array{display: string, tier: string, source: string, observed: string, prices: list<float>, regions: array<string, bool>}> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            if ($row['region_code'] === null) {
                continue;
            }

            $key = $row['crop_slug'].'|'.$row['tier'];
            $grouped[$key] ??= [
                'display' => $row['crop_display_name'],
                'tier' => $row['tier'],
                'source' => $row['source'],
                'observed' => $row['observed_at'],
                'prices' => [],
                'regions' => [],
            ];

            $grouped[$key]['prices'][] = $row['price_per_kg'];
            $grouped[$key]['regions'][$row['region_code']] = true;
            $grouped[$key]['observed'] = max($grouped[$key]['observed'], $row['observed_at']);
        }

        $averages = [];

        foreach ($grouped as $key => $group) {
            [$slug] = explode('|', $key, 2);
            $regionCount = count($group['regions']);

            $averages[] = [
                'crop_slug' => $slug,
                'crop_display_name' => $group['display'],
                'tier' => $group['tier'],
                'price_per_kg' => round(array_sum($group['prices']) / count($group['prices']), 2),
                'currency' => 'PHP',
                'region_code' => null,
                'market_name' => self::NATIONAL_MARKET_LABEL." ({$regionCount} regions)",
                'source' => $group['source'],
                'observed_at' => $group['observed'],
            ];
        }

        return $averages;
    }

    private function recordRun(string $status, int $rows, ?string $error, \DateTimeInterface $startedAt): void
    {
        CropPriceSyncRun::create([
            'status' => $status,
            'rows_upserted' => $rows,
            'error' => $error,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }
}
