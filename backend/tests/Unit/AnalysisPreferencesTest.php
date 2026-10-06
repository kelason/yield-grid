<?php

use App\Domain\CropRecommendation\DTOs\AnalysisPreferences;
use App\Domain\CropRecommendation\Enums\AnalysisGoal;
use App\Domain\CropRecommendation\Enums\IrrigationLevel;

it('keeps valid preference values', function () {
    $prefs = AnalysisPreferences::fromArray([
        'produce_types' => ['fruit'],
        'subtypes' => ['citrus', 'berry'],
        'irrigation' => 'limited',
        'goal' => 'quick_cash',
    ]);

    expect($prefs->produceTypes)->toBe(['fruit'])
        ->and($prefs->subtypes)->toBe(['citrus', 'berry'])
        ->and($prefs->irrigation)->toBe(IrrigationLevel::LIMITED)
        ->and($prefs->goal)->toBe(AnalysisGoal::QUICK_CASH);
});

it('drops unknown slugs and invalid option values', function () {
    $prefs = AnalysisPreferences::fromArray([
        'produce_types' => ['fruit', 'spaceship'],
        'subtypes' => ['nope'],
        'irrigation' => 'firehose',
        'goal' => 'world_domination',
    ]);

    expect($prefs->produceTypes)->toBe(['fruit'])
        ->and($prefs->subtypes)->toBe([])
        ->and($prefs->irrigation)->toBeNull()
        ->and($prefs->goal)->toBeNull();
});

it('prefers explicit subtypes over type expansion', function () {
    $explicit = AnalysisPreferences::fromArray(['subtypes' => ['citrus']]);
    $expanded = AnalysisPreferences::fromArray(['produce_types' => ['fruit']]);
    $open = AnalysisPreferences::fromArray([]);

    expect($explicit->effectiveSubtypes())->toBe(['citrus'])
        ->and($expanded->effectiveSubtypes())->toBe(['tropical_tree', 'citrus', 'vine_ground', 'berry'])
        ->and($open->effectiveSubtypes())->toBe([]);
});

it('round-trips through arrays', function () {
    $input = [
        'produce_types' => ['vegetable'],
        'subtypes' => [],
        'irrigation' => 'reliable',
        'goal' => 'max_profit',
    ];

    expect(AnalysisPreferences::fromArray($input)->toArray())->toBe($input);
});

it('reports whether any preference was set', function () {
    expect(AnalysisPreferences::fromArray([])->isEmpty())->toBeTrue()
        ->and(AnalysisPreferences::fromArray(['subtypes' => ['citrus']])->isEmpty())->toBeFalse();
});

it('labels every irrigation level and goal', function () {
    foreach (IrrigationLevel::cases() as $level) {
        expect($level->label())->not->toBe('');
    }

    foreach (AnalysisGoal::cases() as $goal) {
        expect($goal->label())->not->toBe('');
    }
});

it('dedupes repeated slugs', function () {
    $prefs = AnalysisPreferences::fromArray([
        'produce_types' => ['fruit', 'fruit'],
        'subtypes' => ['citrus', 'citrus'],
    ]);

    expect($prefs->produceTypes)->toBe(['fruit'])
        ->and($prefs->subtypes)->toBe(['citrus']);
});
