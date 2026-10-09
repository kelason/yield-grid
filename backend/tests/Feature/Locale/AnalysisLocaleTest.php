<?php

declare(strict_types=1);

use App\Domain\CropRecommendation\Jobs\AnalyzePlotJob;
use App\Domain\CropRecommendation\Prompts\CropAnalysisPrompt;
use App\Domain\CropRecommendation\Services\AgroMonitoringService;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const ANALYSIS_LOCALE_API_KEY = 'analysis-locale-test-secret';

function localeTestPlot(): Plot
{
    $user = User::factory()->create(['role' => 'farmer']);
    $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);

    return Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Plot A',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10.5,
    ]);
}

function fakeCebuanoGemini(): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([[
                        'crop_name' => 'Humay',
                        'confidence_score' => 90,
                        'reasoning' => 'Maayo kini nga humay alang sa imong yuta.',
                        'projected_yield' => '4 tonelada',
                        'produce_type' => 'field_crop',
                        'subtype' => 'staple',
                    ]]),
                ]]],
            ]],
        ]),
    ]);
}

beforeEach(function () {
    config()->set('services.gemini.key', ANALYSIS_LOCALE_API_KEY);
    config()->set('services.gemini.model', 'gemini-test-model');
    Cache::flush();
    Event::fake();

    $this->mock(AgroMonitoringService::class, function ($mock) {
        $mock->shouldReceive('getPlotData')->andReturn([
            'weather' => ['temp' => 300],
            'soil' => ['moisture' => 0.3],
        ]);
    });
});

it('instructs the model in Cebuano and stores the reply verbatim', function (): void {
    fakeCebuanoGemini();
    $plot = localeTestPlot();

    AnalyzePlotJob::dispatchSync($plot->id, null, 'ceb');

    Http::assertSent(function ($request) {
        $text = $request['contents'][0]['parts'][0]['text'] ?? '';

        return str_contains($text, 'Respond in Cebuano.');
    });

    $rec = CropRecommendation::where('plot_id', $plot->id)->firstOrFail();
    expect($rec->reasoning)->toBe('Maayo kini nga humay alang sa imong yuta.');
});

it('adds no language instruction for the default locale', function (): void {
    fakeCebuanoGemini();
    $plot = localeTestPlot();

    AnalyzePlotJob::dispatchSync($plot->id);

    Http::assertSent(function ($request) {
        $text = $request['contents'][0]['parts'][0]['text'] ?? '';

        return ! str_contains($text, 'Respond in ');
    });
});

it('caches English and Cebuano analyses separately', function (): void {
    fakeCebuanoGemini();
    $plot = localeTestPlot();

    AnalyzePlotJob::dispatchSync($plot->id);
    AnalyzePlotJob::dispatchSync($plot->id);
    AnalyzePlotJob::dispatchSync($plot->id, null, 'ceb');

    Http::assertSentCount(2);
});

it('renders identical prompts for English with and without the locale arg', function (): void {
    $plot = new Plot;
    $plot->name = 'Plot A';
    $plot->calculated_area = 10.5;
    $plot->soil_type = 'clay';

    $legacy = CropAnalysisPrompt::render($plot, [], [], []);
    $explicit = CropAnalysisPrompt::render($plot, [], [], [], 'en');

    expect($explicit)->toBe($legacy);
});

it('dispatches the job with the farmer locale from the endpoint', function (): void {
    Queue::fake();

    $user = User::factory()->create(['role' => 'farmer', 'locale' => 'ceb']);
    $farm = Farm::create(['user_id' => $user->id, 'name' => 'Test Farm']);
    $plot = Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Plot A',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'clay',
        'calculated_area' => 10.5,
    ]);

    $this->actingAs($user)->postJson("/api/v1/plots/{$plot->id}/analyze", [])
        ->assertStatus(202);

    Queue::assertPushed(AnalyzePlotJob::class, function (AnalyzePlotJob $job) use ($plot) {
        return $job->plotId === $plot->id && $job->locale === 'ceb';
    });
});
