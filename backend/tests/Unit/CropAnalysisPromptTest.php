<?php

use App\Domain\CropRecommendation\Prompts\CropAnalysisPrompt;
use Domain\Farming\Models\Plot;

function promptPlot(): Plot
{
    $plot = new Plot;
    $plot->name = 'Kilometer 7';
    $plot->calculated_area = 2.5;
    $plot->soil_type = 'clay';

    return $plot;
}

function promptAgro(): array
{
    return [
        'weather' => ['temp' => 301, 'humidity' => 80],
        'soil' => ['moisture' => 0.32],
    ];
}

function promptLocation(): array
{
    return ['city' => 'Tarlac', 'state' => 'Tarlac', 'country' => 'Philippines'];
}

it('renders plot, soil, weather, and location facts', function () {
    $prompt = CropAnalysisPrompt::render(promptPlot(), promptAgro(), promptLocation());

    expect($prompt)->toContain('Kilometer 7')
        ->and($prompt)->toContain('2.5')
        ->and($prompt)->toContain('clay')
        ->and($prompt)->toContain('Tarlac, Tarlac, Philippines')
        ->and($prompt)->toContain('"temp":301')
        ->and($prompt)->toContain('"moisture":0.32');
});

it('demands a strict ten-object JSON schema', function () {
    $prompt = CropAnalysisPrompt::render(promptPlot(), promptAgro(), promptLocation());

    expect($prompt)->toContain('JSON array of 10 objects')
        ->and($prompt)->toContain('"crop_name"')
        ->and($prompt)->toContain('"confidence_score"')
        ->and($prompt)->toContain('"reasoning"')
        ->and($prompt)->toContain('"projected_yield"');
});

it('constrains recommendations to the selected subtypes', function () {
    $prompt = CropAnalysisPrompt::render(promptPlot(), promptAgro(), promptLocation(), [
        'subtypes' => ['citrus', 'berry'],
    ]);

    expect($prompt)->toContain('citrus')
        ->and($prompt)->toContain('berry')
        ->and($prompt)->toContain('"produce_type"')
        ->and($prompt)->toContain('"subtype"');
});

it('lists market demand and plot history', function () {
    $prompt = CropAnalysisPrompt::render(promptPlot(), promptAgro(), promptLocation(), [
        'demands' => [
            ['crop' => 'Calamansi', 'quantity_kg' => 500, 'target_price_per_kg' => 45, 'needed_by' => '2026-11-01'],
        ],
        'previous_crops' => ['tomato'],
    ]);

    expect($prompt)->toContain('Calamansi')
        ->and($prompt)->toContain('500')
        ->and($prompt)->toContain('tomato');
});

it('injects farm context, season, and rotation guidance', function () {
    $prompt = CropAnalysisPrompt::render(promptPlot(), promptAgro(), promptLocation(), [
        'irrigation' => 'none',
        'goal' => 'quick_cash',
        'season' => 'dry',
        'date' => '2026-10-06',
    ]);

    expect($prompt)->toContain('none')
        ->and($prompt)->toContain('quick_cash')
        ->and($prompt)->toContain('dry')
        ->and($prompt)->toContain('2026-10-06')
        ->and(strtolower($prompt))->toContain('rotation');
});
