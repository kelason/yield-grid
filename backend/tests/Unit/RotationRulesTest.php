<?php

use App\Domain\CropRecommendation\Rotation\RotationRules;

it('penalizes planting the same risky family back to back', function () {
    expect(RotationRules::successionPenalty('nightshade', 'nightshade'))->toBeGreaterThan(0)
        ->and(RotationRules::successionPenalty('cucurbit', 'cucurbit'))->toBeGreaterThan(0)
        ->and(RotationRules::successionPenalty('brassica', 'brassica'))->toBeGreaterThan(0);
});

it('ignores succession when families differ or history is unknown', function () {
    expect(RotationRules::successionPenalty('legume', 'nightshade'))->toBe(0)
        ->and(RotationRules::successionPenalty(null, 'nightshade'))->toBe(0);
});

it('rewards nitrogen builders ahead of heavy feeders', function () {
    expect(RotationRules::soilSequenceBonus('builder', 'heavy'))->toBeGreaterThan(0)
        ->and(RotationRules::soilSequenceBonus('heavy', 'builder'))->toBeGreaterThan(0)
        ->and(RotationRules::soilSequenceBonus('heavy', 'heavy'))->toBe(0)
        ->and(RotationRules::soilSequenceBonus(null, 'heavy'))->toBe(0);
});

it('suggests legume follow-ups after hungry crops', function () {
    expect(RotationRules::goodFollowups('grass'))->toContain('legume')
        ->and(RotationRules::goodFollowups('nightshade'))->toContain('legume');
});

it('flags known bad companions in both directions', function () {
    expect(RotationRules::avoidCompanions('allium'))->toContain('legume')
        ->and(RotationRules::avoidCompanions('legume'))->toContain('allium')
        ->and(RotationRules::avoidCompanions('nightshade'))->toContain('grass')
        ->and(RotationRules::avoidCompanions('nightshade'))->toContain('cucurbit', 'brassica')
        ->and(RotationRules::avoidCompanions('cucurbit'))->toContain('nightshade')
        ->and(RotationRules::avoidCompanions('brassica'))->toContain('nightshade');
});

it('names specific clashing crop pairs symmetrically', function () {
    expect(RotationRules::clashReason('carrot', 'dill'))->toBeString()
        ->and(RotationRules::clashReason('dill', 'carrot'))->toBe(RotationRules::clashReason('carrot', 'dill'))
        ->and(RotationRules::clashReason('carrot', 'lettuce'))->toBeNull();
});

it('renders rotation guidance for the prompt', function () {
    $section = RotationRules::promptSection();

    expect(strtolower($section))->toContain('rotation')
        ->and(strtolower($section))->toContain('companion');
});
