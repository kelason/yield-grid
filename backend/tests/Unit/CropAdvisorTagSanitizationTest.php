<?php

use App\Domain\CropRecommendation\Actions\BuildsAnalysisContext;
use App\Domain\CropRecommendation\Services\CropAdvisorService;
use App\Infrastructure\Services\ReverseGeocodeService;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

const SANITIZE_TEST_API_KEY = 'sanitize-test-secret';

beforeEach(function () {
    config()->set('services.gemini.key', SANITIZE_TEST_API_KEY);
    config()->set('services.gemini.model', 'gemini-test-model');
});

final class SanitizeFakeContext implements BuildsAnalysisContext
{
    public function execute(Plot $plot, ?array $preferences): array
    {
        return [];
    }
}

function sanitizeAdvisorService(): CropAdvisorService
{
    return new CropAdvisorService(new ReverseGeocodeService, new SanitizeFakeContext);
}

function sanitizePlot(): Plot
{
    $plot = new Plot;
    $plot->name = 'Test Plot';
    $plot->calculated_area = 1.0;

    return $plot;
}

function sanitizeFakeGemini(array $recs): void
{
    $text = json_encode($recs);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
        ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]]
    )]);
}

it('nulls taxonomy tags outside the catalog', function () {
    sanitizeFakeGemini([
        ['crop_name' => 'Tomato', 'confidence_score' => 90, 'reasoning' => 'r', 'projected_yield' => 'y', 'produce_type' => 'vegetable', 'subtype' => 'fruiting'],
        ['crop_name' => 'Moon Melon', 'confidence_score' => 80, 'reasoning' => 'r', 'projected_yield' => 'y', 'produce_type' => 'Galaxy', 'subtype' => 'this-subtype-name-is-way-too-long-for-varchar'],
    ]);

    $recs = sanitizeAdvisorService()->getRecommendations(sanitizePlot(), []);

    expect($recs[0]['produce_type'])->toBe('vegetable')
        ->and($recs[0]['subtype'])->toBe('fruiting')
        ->and($recs[1]['produce_type'])->toBeNull()
        ->and($recs[1]['subtype'])->toBeNull();
});

it('rejects subtypes outside the tagged produce type', function () {
    sanitizeFakeGemini([
        ['crop_name' => 'Mango', 'confidence_score' => 90, 'reasoning' => 'r', 'projected_yield' => 'y', 'produce_type' => 'fruit', 'subtype' => 'fruiting'],
    ]);

    $recs = sanitizeAdvisorService()->getRecommendations(sanitizePlot(), []);

    expect($recs[0]['produce_type'])->toBe('fruit')
        ->and($recs[0]['subtype'])->toBe('tropical_tree');
});

it('backfills cleared tags from the crop name', function () {
    sanitizeFakeGemini([
        ['crop_name' => 'Calamansi', 'confidence_score' => 90, 'reasoning' => 'r', 'projected_yield' => 'y', 'produce_type' => 'Fruit', 'subtype' => null],
        ['crop_name' => 'Mango', 'confidence_score' => 90, 'reasoning' => 'r', 'projected_yield' => 'y'],
    ]);

    $recs = sanitizeAdvisorService()->getRecommendations(sanitizePlot(), []);

    expect($recs[0]['produce_type'])->toBe('fruit')
        ->and($recs[0]['subtype'])->toBe('citrus')
        ->and($recs[1]['produce_type'])->toBe('fruit')
        ->and($recs[1]['subtype'])->toBe('tropical_tree');
});
