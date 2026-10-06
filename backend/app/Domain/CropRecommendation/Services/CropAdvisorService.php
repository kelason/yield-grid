<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Services;

use App\Domain\CropRecommendation\Actions\BuildsAnalysisContext;
use App\Domain\CropRecommendation\Catalog\MockCropCatalog;
use App\Domain\CropRecommendation\Prompts\CropAnalysisPrompt;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;
use App\Infrastructure\Services\GeminiHttpHelper;
use App\Infrastructure\Services\ReverseGeocodeService;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CropAdvisorService
{
    private const REQUEST_TIMEOUT_SECONDS = 12;

    private const MAX_RETRIES = 1;

    private const RETRY_DELAY_MS = 0;

    private const RATE_LIMIT_DECAY_SECONDS = 60;

    private string $apiKey;

    private int $cacheTtlSeconds = 1800; // 30 minutes recommendation cache

    private int $rateLimitRpm = 10;      // Max 10 requests per minute for Gemini API

    public function __construct(
        private readonly ReverseGeocodeService $geocoding,
        private readonly BuildsAnalysisContext $contextBuilder
    ) {
        $this->apiKey = (string) (config('services.gemini.key') ?? '');
    }

    /** @param array<string, mixed>|null $preferences */
    public function getRecommendations(Plot $plot, array $agroData, ?array $preferences = null): array
    {
        $location = $this->geocoding->resolvePlotLocation($plot);
        $context = $this->buildContext($plot, $preferences);
        $prompt = CropAnalysisPrompt::render($plot, $agroData, $location, $context);

        // 1. Check AI recommendation cache first to avoid re-querying Gemini for identical plot inputs
        $cacheKey = 'gemini_crop_rec_'.md5($plot->id.'_'.$prompt);
        $cachedRecs = Cache::get($cacheKey);
        if (is_array($cachedRecs) && count($cachedRecs) > 0) {
            Log::info("Serving cached crop recommendations for plot {$plot->id}.");

            return $cachedRecs;
        }

        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is missing. Using location-tailored mock recommendations.');

            return $this->mockRecommendations($plot, $location, $context);
        }

        // 2. Check Gemini rate limiter
        $rateKey = 'gemini_api_rate_limit';
        if (RateLimiter::tooManyAttempts($rateKey, $this->rateLimitRpm)) {
            $seconds = RateLimiter::availableIn($rateKey);
            Log::warning("Gemini API rate limit reached ({$this->rateLimitRpm} RPM). Backing off for {$seconds}s. Serving localized recommendations.");

            return $this->mockRecommendations($plot, $location, $context);
        }

        RateLimiter::hit($rateKey, self::RATE_LIMIT_DECAY_SECONDS);

        try {
            $response = GeminiHttpHelper::postGenerateContent(
                $this->apiKey,
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ],
                self::REQUEST_TIMEOUT_SECONDS,
                self::MAX_RETRIES,
                self::RETRY_DELAY_MS
            );

            if ($response !== null) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

                $decoded = json_decode($text, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $decoded = $this->sanitizeTags($decoded);
                    // Cache the successful Gemini output for 30 minutes
                    Cache::put($cacheKey, $decoded, $this->cacheTtlSeconds);

                    return $decoded;
                }

                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            } else {
                Log::error('Gemini API error', ['models' => GeminiHttpHelper::models()]);
            }
        } catch (\Exception $e) {
            Log::error('Gemini Request Exception', ['message' => GeminiHttpHelper::redact($e->getMessage(), $this->apiKey)]);
        }

        return $this->mockRecommendations($plot, $location, $context);
    }

    /**
     * @param  array{city?: string, state?: string, country?: string}  $location
     * @param  array{subtypes?: list<string>, irrigation?: string, season?: string, previous_crops?: list<string>, demands?: list<array{crop: string}>}  $context
     * @return list<array{crop_name: string, confidence_score: int, reasoning: string, projected_yield: string, produce_type: string|null, subtype: string|null}>
     */
    private function mockRecommendations(Plot $plot, array $location, array $context): array
    {
        $soil = $plot->soil_type instanceof \BackedEnum ? $plot->soil_type->value : (string) ($plot->soil_type ?? 'loamy');

        return MockCropCatalog::recommend((float) ($plot->calculated_area ?? 1.0), $soil, $location, $context);
    }

    /** @param array<string, mixed>|null $preferences */
    private function buildContext(Plot $plot, ?array $preferences): array
    {
        return $this->contextBuilder->execute($plot, $preferences);
    }

    /**
     * Null out LLM taxonomy tags outside the catalog (which would
     * overflow the columns or fragment the type filter), backfilling
     * from the crop name when it resolves to a catalog slug.
     *
     * @param  array<int, mixed>  $recs
     * @return array<int, mixed>
     */
    private function sanitizeTags(array $recs): array
    {
        foreach ($recs as $index => $rec) {
            if (! is_array($rec)) {
                continue;
            }

            $type = $this->validSlug($rec['produce_type'] ?? null, CropTaxonomy::typeSlugs());
            $subtype = $this->validSlug($rec['subtype'] ?? null, CropTaxonomy::subtypeSlugs());

            if ($type !== null && $subtype !== null && (CropTaxonomy::subtypes()[$subtype]['type'] ?? null) !== $type) {
                $subtype = null;
            }

            if ($type === null || $subtype === null) {
                $slug = CropTaxonomy::matchSlug((string) ($rec['crop_name'] ?? ''));
                $profile = $slug === null ? null : CropTaxonomy::profile($slug);
                $type ??= $profile['type'] ?? null;
                $subtype ??= $profile['subtype'] ?? null;
            }

            $recs[$index]['produce_type'] = $type;
            $recs[$index]['subtype'] = $subtype;
        }

        return $recs;
    }

    /**
     * @param  list<string>  $allowed
     */
    private function validSlug(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }
}
