<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Marketplace\Prompts\CropNamePrompt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Gemini-backed crop-name normalizer. Same provider as crop recommendations.
 * Only ever called from queued jobs — never in the HTTP lifecycle.
 */
final class CropNameAiNormalizer
{
    private const REQUEST_TIMEOUT_SECONDS = 12;

    private const MAX_RETRIES = 2;

    private const RETRY_DELAY_MS = 1000;

    private const RATE_LIMIT_KEY = 'gemini_api_rate_limit';

    private const RATE_LIMIT_RPM = 10;

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.key') ?? '');
    }

    /**
     * @param  list<string>  $knownSlugs
     * @return array{slug: ?string, confidence: int}
     */
    public function normalize(string $input, array $knownSlugs): array
    {
        if ($this->apiKey === '' || $knownSlugs === []) {
            return ['slug' => null, 'confidence' => 0];
        }

        if (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_RPM)) {
            Log::warning('Crop-name AI normalization skipped: Gemini rate limit reached.');

            return ['slug' => null, 'confidence' => 0];
        }

        RateLimiter::hit(self::RATE_LIMIT_KEY, self::RATE_LIMIT_DECAY_SECONDS);
        $startTime = microtime(true);

        try {
            $response = GeminiHttpHelper::postGenerateContent(
                $this->apiKey,
                [
                    'contents' => [
                        ['parts' => [['text' => CropNamePrompt::render($input, $knownSlugs)]]],
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
                Log::error('Crop-name AI normalization failed', ['models' => GeminiHttpHelper::models()]);

                return ['slug' => null, 'confidence' => 0];
            }

            $text = $response->json('candidates.0.content.parts.0.text', '{}');
            $decoded = json_decode((string) $text, true);

            $slug = is_array($decoded) && isset($decoded['match']) && is_string($decoded['match'])
                ? $decoded['match']
                : null;
            $confidence = is_array($decoded) && isset($decoded['confidence']) && is_numeric($decoded['confidence'])
                ? (int) $decoded['confidence']
                : 0;

            if ($slug !== null && ! in_array($slug, $knownSlugs, true)) {
                Log::warning('Crop-name AI returned an unknown slug.', ['slug' => $slug]);

                return ['slug' => null, 'confidence' => 0];
            }

            Log::info('Crop-name AI normalization completed', [
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'matched' => $slug !== null,
            ]);

            return ['slug' => $slug, 'confidence' => $confidence];
        } catch (\Throwable $e) {
            Log::error('Crop-name AI normalization exception', ['message' => GeminiHttpHelper::redact($e->getMessage(), $this->apiKey)]);

            return ['slug' => null, 'confidence' => 0];
        }
    }
}
