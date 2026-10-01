<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use App\Domain\Marketplace\Prompts\CropPriceEstimatePrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Gemini-backed crop price estimator. Produces clearly-labelled AI guesses
 * used only when no DA or marketplace data exists. Only ever called from
 * queued jobs — never in the HTTP lifecycle.
 */
final class AiPriceEstimatorService
{
    private const REQUEST_TIMEOUT_SECONDS = 20;

    private const MAX_RETRIES = 2;

    private const RETRY_DELAY_MS = 1000;

    private const RATE_LIMIT_KEY = 'gemini_api_rate_limit';

    private const RATE_LIMIT_RPM = 10;

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    public const MARKET_NAME = 'AI estimate';

    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.key') ?? '');
        $this->model = (string) (config('services.gemini.model', 'gemini-3.5-flash-lite'));
    }

    /**
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    public function estimate(string $input, string $slug): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        if (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_RPM)) {
            Log::warning('Crop price AI estimation skipped: Gemini rate limit reached.');

            return [];
        }

        RateLimiter::hit(self::RATE_LIMIT_KEY, self::RATE_LIMIT_DECAY_SECONDS);
        $startTime = microtime(true);

        try {
            $response = Http::timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->retry(self::MAX_RETRIES, self::RETRY_DELAY_MS, throw: false)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => CropPriceEstimatePrompt::render($input)]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Crop price AI estimation failed', ['status' => $response->status()]);

                return [];
            }

            $decoded = json_decode((string) $response->json('candidates.0.content.parts.0.text', '{}'), true);

            if (! is_array($decoded) || ($decoded['is_crop'] ?? false) !== true) {
                return [];
            }

            $rows = $this->buildRows($slug, $decoded);

            Log::info('Crop price AI estimation completed', [
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'tiers' => count($rows),
            ]);

            return $rows;
        } catch (\Throwable $e) {
            Log::error('Crop price AI estimation exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    private function buildRows(string $slug, array $decoded): array
    {
        $tiers = $decoded['tiers'] ?? null;

        if (! is_array($tiers)) {
            return [];
        }

        $displayName = isset($decoded['display_name']) && is_string($decoded['display_name']) && trim($decoded['display_name']) !== ''
            ? trim($decoded['display_name'])
            : Str::headline($slug);

        $rows = [];

        foreach (PriceTier::cases() as $tier) {
            $price = $tiers[$tier->value] ?? null;

            if (! is_numeric($price)) {
                continue;
            }

            $price = (float) $price;

            if ($price <= 0 || $price > MarketplaceConstants::PRICE_GUIDE_AI_ESTIMATE_MAX_PRICE) {
                continue;
            }

            $rows[] = [
                'crop_slug' => $slug,
                'crop_display_name' => $displayName,
                'tier' => $tier->value,
                'price_per_kg' => round($price, 2),
                'currency' => 'PHP',
                'region_code' => null,
                'market_name' => self::MARKET_NAME,
                'source' => PriceSource::AI_ESTIMATE->value,
                'observed_at' => now()->toDateString(),
            ];
        }

        return $rows;
    }
}
