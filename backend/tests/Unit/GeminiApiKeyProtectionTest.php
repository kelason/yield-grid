<?php

use App\Domain\CropRecommendation\Services\CropAdvisorService;
use App\Infrastructure\Services\AiPriceEstimatorService;
use App\Infrastructure\Services\AiPriceExtractorService;
use App\Infrastructure\Services\CropNameAiNormalizer;
use App\Infrastructure\Services\GeminiHttpHelper;
use App\Infrastructure\Services\ReverseGeocodeService;
use Domain\Farming\Models\Plot;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

const GEMINI_TEST_API_KEY = 'test-gemini-secret-key';

beforeEach(function () {
    config()->set('services.gemini.key', GEMINI_TEST_API_KEY);
    config()->set('services.gemini.model', 'gemini-test-model');
});

function geminiRequestIsProtected(Request $request): bool
{
    $uri = (string) $request->toPsrRequest()->getUri();

    return $request->header(GeminiHttpHelper::API_KEY_HEADER) === [GEMINI_TEST_API_KEY]
        && ! str_contains($uri, GEMINI_TEST_API_KEY)
        && parse_url($uri, PHP_URL_QUERY) === null;
}

function geminiFakeResponse(string $text): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
}

function makeAdvisorService(): CropAdvisorService
{
    // Real geocoder: with an unpersisted plot (no centroid, no farm) it
    // short-circuits to fallback constants without HTTP or DB access.
    return new CropAdvisorService(new ReverseGeocodeService);
}

function makeUnpersistedPlot(): Plot
{
    $plot = new Plot;
    $plot->name = 'Test Plot';
    $plot->calculated_area = 1.0;

    return $plot;
}

it('sends the extractor API key via header instead of URL', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(geminiFakeResponse('[]'))]);

    (new AiPriceExtractorService)->extract('<p>Rice retail 50</p>');

    Http::assertSent(fn (Request $request) => geminiRequestIsProtected($request));
});

it('sends the estimator API key via header instead of URL', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(geminiFakeResponse('{}'))]);

    (new AiPriceEstimatorService)->estimate('rice', 'rice');

    Http::assertSent(fn (Request $request) => geminiRequestIsProtected($request));
});

it('sends the normalizer API key via header instead of URL', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(geminiFakeResponse('{}'))]);

    (new CropNameAiNormalizer)->normalize('kamote', ['sweet-potato']);

    Http::assertSent(fn (Request $request) => geminiRequestIsProtected($request));
});

it('sends the advisor API key via header instead of URL', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(geminiFakeResponse('[]'))]);

    makeAdvisorService()->getRecommendations(makeUnpersistedPlot(), []);

    Http::assertSent(fn (Request $request) => geminiRequestIsProtected($request));
});

it('builds a keyless generateContent URL', function () {
    $url = GeminiHttpHelper::generateContentUrl('gemini-test-model');

    expect($url)->toBe('https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model:generateContent')
        ->and(parse_url($url, PHP_URL_QUERY))->toBeNull();
});

it('redacts secrets from messages', function () {
    expect(GeminiHttpHelper::redact('key='.GEMINI_TEST_API_KEY.' leaked', GEMINI_TEST_API_KEY))
        ->toBe('key='.GeminiHttpHelper::REDACTED.' leaked');
});

it('leaves messages unchanged when the secret is empty', function () {
    expect(GeminiHttpHelper::redact('nothing to hide', ''))->toBe('nothing to hide');
});
