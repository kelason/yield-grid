<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Gemini fallback that extracts structured price rows from DA page text when
 * the HTML parser finds nothing. Runs inside the daily sync only.
 */
final class AiPriceExtractorService
{
    private const REQUEST_TIMEOUT_SECONDS = 30;

    private const MAX_RETRIES = 2;

    private const RETRY_DELAY_MS = 2000;

    private const RATE_LIMIT_KEY = 'gemini_api_rate_limit';

    private const RATE_LIMIT_RPM = 10;

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.key') ?? '');
    }

    /**
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    public function extract(string $html): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        if (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_RPM)) {
            Log::warning('DA price AI extraction skipped: Gemini rate limit reached.');

            return [];
        }

        RateLimiter::hit(self::RATE_LIMIT_KEY, self::RATE_LIMIT_DECAY_SECONDS);

        $text = mb_substr(
            trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? ''),
            0,
            MarketplaceConstants::PRICE_GUIDE_AI_EXTRACT_MAX_CHARS
        );

        if ($text === '') {
            return [];
        }

        try {
            $response = GeminiHttpHelper::postGenerateContent(
                $this->apiKey,
                [
                    'contents' => [
                        ['parts' => [['text' => $this->buildPrompt($text)]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ],
                self::REQUEST_TIMEOUT_SECONDS,
                self::MAX_RETRIES,
                self::RETRY_DELAY_MS
            );

            if ($response === null) {
                Log::error('DA price AI extraction failed', ['models' => GeminiHttpHelper::models()]);

                return [];
            }

            $decoded = json_decode((string) $response->json('candidates.0.content.parts.0.text', '[]'), true);

            if (! is_array($decoded)) {
                return [];
            }

            return $this->validateRows($decoded);
        } catch (\Throwable $e) {
            Log::error('DA price AI extraction exception', ['message' => GeminiHttpHelper::redact($e->getMessage(), $this->apiKey)]);

            return [];
        }
    }

    private function buildPrompt(string $text): string
    {
        return <<<PROMPT
            Extract agricultural commodity prices from this Department of Agriculture (Philippines) price monitoring page text.

            ## Page text
            {$text}

            ## Output format
            Respond with a JSON array only (no markdown fences). Each item must have exactly these keys:
            - crop_name (string, e.g. "Rice", "Tomato")
            - tier (one of: farmgate, wholesale, retail — use retail unless the text clearly says otherwise)
            - price_per_kg (number, in Philippine pesos)
            - market (string or null, the market name when mentioned)

            Rules: only include rows with a real numeric price from the text. Never invent prices. Return [] when no prices are present.
            PROMPT;
    }

    /**
     * @return list<array{crop_slug: string, crop_display_name: string, tier: string, price_per_kg: float, currency: string, region_code: ?string, market_name: ?string, source: string, observed_at: string}>
     */
    private function validateRows(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        $rows = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = isset($item['crop_name']) && is_string($item['crop_name']) ? trim($item['crop_name']) : '';
            $tier = isset($item['tier']) && is_string($item['tier']) ? strtolower($item['tier']) : '';
            $price = $item['price_per_kg'] ?? null;

            if ($name === '' || Str::slug($name) === '' || ! is_numeric($price) || (float) $price <= 0) {
                continue;
            }

            if (PriceTier::tryFrom($tier) === null) {
                continue;
            }

            $rows[] = [
                'crop_slug' => Str::slug($name),
                'crop_display_name' => $name,
                'tier' => $tier,
                'price_per_kg' => round((float) $price, 2),
                'currency' => 'PHP',
                'region_code' => null,
                'market_name' => isset($item['market']) && is_string($item['market']) ? $item['market'] : null,
                'source' => PriceSource::DA_BANTAY_PRESYO->value,
                'observed_at' => now()->toDateString(),
            ];
        }

        return $rows;
    }
}
