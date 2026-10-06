<?php

use App\Domain\CropRecommendation\Actions\BuildsAnalysisContext;
use App\Domain\CropRecommendation\Services\CropAdvisorService;
use App\Infrastructure\Services\AiPriceEstimatorService;
use App\Infrastructure\Services\AiPriceExtractorService;
use App\Infrastructure\Services\CropNameAiNormalizer;
use App\Infrastructure\Services\GeminiHttpHelper;
use App\Infrastructure\Services\ReverseGeocodeService;
use Domain\Farming\Models\Plot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

const FALLBACK_TEST_API_KEY = 'fallback-test-secret';

const FALLBACK_PRIMARY_MODEL = 'gemini-3.5-flash-lite';

const FALLBACK_MODEL = 'gemini-3.1-flash-lite';

beforeEach(function () {
    config()->set('services.gemini.key', FALLBACK_TEST_API_KEY);
    config()->set('services.gemini.model', FALLBACK_PRIMARY_MODEL);
    config()->set('services.gemini.fallback_model', FALLBACK_MODEL);
});

function fallbackPayload(): array
{
    return ['contents' => [['parts' => [['text' => 'hi']]]]];
}

function fallbackGeminiText(string $text): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
}

final class FallbackFakeContext implements BuildsAnalysisContext
{
    public function execute(Plot $plot, ?array $preferences): array
    {
        return [];
    }
}

function fallbackAdvisorService(): CropAdvisorService
{
    return new CropAdvisorService(new ReverseGeocodeService, new FallbackFakeContext);
}

function fallbackPlot(): Plot
{
    $plot = new Plot;
    $plot->name = 'Test Plot';
    $plot->calculated_area = 1.0;

    return $plot;
}

it('lists primary then fallback models from config', function () {
    expect(GeminiHttpHelper::models())->toBe([FALLBACK_PRIMARY_MODEL, FALLBACK_MODEL]);
});

it('uses the primary model without fallback when it succeeds', function () {
    Http::fake(fn () => Http::response(['ok' => true]));

    $response = GeminiHttpHelper::postGenerateContent(FALLBACK_TEST_API_KEY, fallbackPayload(), 5, 1, 0);

    expect($response?->successful())->toBeTrue();
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_PRIMARY_MODEL));
});

it('falls back to 3.1 when the primary model fails', function () {
    Http::fake(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_PRIMARY_MODEL)
        ? Http::response(null, 500)
        : Http::response(['ok' => true, 'model' => 'fallback']));

    $response = GeminiHttpHelper::postGenerateContent(FALLBACK_TEST_API_KEY, fallbackPayload(), 5, 1, 0);

    expect($response?->json('model'))->toBe('fallback');
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_MODEL));
});

it('falls back when the primary model throws', function () {
    Http::fake(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_PRIMARY_MODEL)
        ? throw new ConnectionException('primary down')
        : Http::response(['ok' => true]));

    $response = GeminiHttpHelper::postGenerateContent(FALLBACK_TEST_API_KEY, fallbackPayload(), 5, 1, 0);

    expect($response?->successful())->toBeTrue();
    // Thrown requests are never recorded, so only the fallback request is counted.
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_MODEL));
});

it('returns null when every model fails', function () {
    Http::fake(fn () => Http::response(null, 500));

    $response = GeminiHttpHelper::postGenerateContent(FALLBACK_TEST_API_KEY, fallbackPayload(), 5, 1, 0);

    expect($response)->toBeNull();
    Http::assertSentCount(2);
});

it('tries only once when primary and fallback are the same model', function () {
    config()->set('services.gemini.fallback_model', FALLBACK_PRIMARY_MODEL);

    Http::fake(fn () => Http::response(null, 500));

    expect(GeminiHttpHelper::postGenerateContent(FALLBACK_TEST_API_KEY, fallbackPayload(), 5, 1, 0))->toBeNull();
    Http::assertSentCount(1);
});

it('redacts the API key from logged model exceptions', function () {
    Http::fake(fn () => throw new ConnectionException('timeout for "https://x.test/?key='.FALLBACK_TEST_API_KEY.'"'));
    Log::spy();

    expect(GeminiHttpHelper::postGenerateContent(FALLBACK_TEST_API_KEY, fallbackPayload(), 5, 1, 0))->toBeNull();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $message === 'Gemini model request exception'
            && ($context['message'] ?? null) === 'timeout for "https://x.test/?key='.GeminiHttpHelper::REDACTED.'"')
        ->twice();
});

it('parses extractor rows from the fallback model when primary fails', function () {
    Http::fake(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_PRIMARY_MODEL)
        ? Http::response(null, 500)
        : Http::response(fallbackGeminiText(json_encode([
            ['crop_name' => 'Rice', 'tier' => 'retail', 'price_per_kg' => 50, 'market' => 'Commonwealth'],
        ]))));

    $rows = (new AiPriceExtractorService)->extract('<p>Rice retail 50</p>');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['crop_slug'])->toBe('rice')
        ->and($rows[0]['price_per_kg'])->toBe(50.0);
    Http::assertSent(fn (Request $request) => str_contains((string) $request->url(), FALLBACK_MODEL));
});

it('returns the extractor default when all models fail', function () {
    Http::fake(fn () => Http::response(null, 500));

    expect((new AiPriceExtractorService)->extract('<p>Rice retail 50</p>'))->toBe([]);
});

it('returns the estimator default when all models fail', function () {
    Http::fake(fn () => Http::response(null, 500));

    expect((new AiPriceEstimatorService)->estimate('rice', 'rice'))->toBe([]);
});

it('returns the normalizer default when all models fail', function () {
    Http::fake(fn () => Http::response(null, 500));

    expect((new CropNameAiNormalizer)->normalize('kamote', ['sweet-potato']))->toBe(['slug' => null, 'confidence' => 0]);
});

it('returns advisor mock recommendations when all models fail', function () {
    Http::fake(fn () => Http::response(null, 500));
    DB::enableQueryLog();

    $recommendations = fallbackAdvisorService()->getRecommendations(fallbackPlot(), []);

    expect($recommendations)->toBeArray()->not->toBeEmpty();
    expect(DB::getQueryLog())->toBe([]);
});
