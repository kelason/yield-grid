<?php

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function seedVerifiedContractChain(): ForwardContract
{
    $farmer = User::factory()->farmer()->create();
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);
    $plot = Plot::create(['farm_id' => $farm->id, 'name' => 'Plot A', 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
    $recommendation = CropRecommendation::create([
        'plot_id' => $plot->id,
        'status' => RecommendationStatus::ACCEPTED,
        'crop_name' => 'Jasmine Rice',
        'projected_yield' => 500,
        'confidence_score' => 90,
        'reasoning' => 'Good soil',
    ]);

    return ForwardContract::factory()->available()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendation->id,
    ]);
}

function countPlotAndFarmQueries(): int
{
    return collect(DB::getQueryLog())
        ->filter(fn (array $entry): bool => (bool) preg_match('/from\s+"?(plots|farms)"?/i', $entry['query']))
        ->count();
}

it('keeps plot and farm queries constant as marketplace contracts grow', function () {
    seedVerifiedContractChain();
    DB::enableQueryLog();
    $this->getJson('/api/v1/market/contracts')->assertOk();
    $single = countPlotAndFarmQueries();

    seedVerifiedContractChain();
    seedVerifiedContractChain();
    DB::flushQueryLog();
    $this->getJson('/api/v1/market/contracts')->assertOk();
    $tripled = countPlotAndFarmQueries();
    DB::disableQueryLog();

    expect($tripled)->toBe($single);
});

it('keeps plot and farm queries constant as farmer contracts grow', function () {
    $first = seedVerifiedContractChain();
    $farmer = User::find($first->farmer_id);
    DB::enableQueryLog();
    $this->actingAs($farmer)->getJson('/api/v1/farmer/contracts')->assertOk();
    $single = countPlotAndFarmQueries();

    // Extra contracts for other farmers must not leak in, so they share this farmer.
    foreach (range(1, 2) as $index) {
        $farm = Farm::create(['user_id' => $farmer->id, 'name' => "Extra Farm {$index}"]);
        $plot = Plot::create(['farm_id' => $farm->id, 'name' => "Extra Plot {$index}", 'polygon' => '{"type": "Polygon", "coordinates": []}', 'soil_type' => 'clay', 'calculated_area' => 10]);
        $recommendation = CropRecommendation::create([
            'plot_id' => $plot->id,
            'status' => RecommendationStatus::ACCEPTED,
            'crop_name' => 'Jasmine Rice',
            'projected_yield' => 500,
            'confidence_score' => 90,
            'reasoning' => 'Good soil',
        ]);
        ForwardContract::factory()->available()->create([
            'farmer_id' => $farmer->id,
            'crop_recommendation_id' => $recommendation->id,
        ]);
    }
    DB::flushQueryLog();
    $this->actingAs($farmer)->getJson('/api/v1/farmer/contracts')->assertOk();
    $tripled = countPlotAndFarmQueries();
    DB::disableQueryLog();

    expect($tripled)->toBe($single);
});
