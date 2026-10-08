<?php

use App\Domain\CropRecommendation\Actions\BuildAnalysisContextAction;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Taxonomy\CropTaxonomy;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function contextPlot(): Plot
{
    $farmer = User::factory()->create(['role' => 'farmer']);
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Ctx Farm']);

    return Plot::create([
        'farm_id' => $farm->id,
        'name' => 'Ctx Plot',
        'polygon' => '{"type": "Polygon", "coordinates": []}',
        'soil_type' => 'loamy',
        'calculated_area' => 2.0,
    ]);
}

function makeDemand(string $crop, float $budget, DemandStatus $status = DemandStatus::OPEN): void
{
    $buyer = User::factory()->create(['role' => 'buyer']);
    $address = UserAddress::create([
        'user_id' => $buyer->id,
        'label' => 'Home',
        'region_code' => '03',
        'city_municipality_code' => '034901',
        'barangay_code' => '034901001',
        'is_default' => true,
    ]);

    CropDemand::create([
        'buyer_id' => $buyer->id,
        'address_id' => $address->id,
        'title' => "{$crop} wanted",
        'crop_name' => $crop,
        'quantity_kg' => 100,
        'remaining_quantity_kg' => 100,
        'target_price_per_kg' => 10,
        'total_budget' => $budget,
        'currency' => 'PHP',
        'needed_by_date' => now()->addDays(30)->toDateString(),
        'expiry_date' => now()->addDays(15)->toDateString(),
        'status' => $status,
    ]);
}

it('carries preferences with season and date', function () {
    $context = app(BuildAnalysisContextAction::class)->execute(contextPlot(), [
        'produce_types' => [],
        'subtypes' => ['citrus'],
        'irrigation' => 'limited',
        'goal' => null,
    ]);

    expect($context['subtypes'])->toBe(['citrus'])
        ->and($context['irrigation'])->toBe('limited')
        ->and($context)->not->toHaveKey('goal')
        ->and($context['season'])->toBe(CropTaxonomy::currentSeason())
        ->and($context['date'])->toBe(now()->toDateString());
});

it('lists open buyer demands by budget', function () {
    makeDemand('Calamansi', 5000);
    makeDemand('Rice', 9000);
    makeDemand('Old Corn', 7000, DemandStatus::FULFILLED);

    $context = app(BuildAnalysisContextAction::class)->execute(contextPlot(), null);

    expect($context['demands'])->toHaveCount(2)
        ->and($context['demands'][0]['crop'])->toBe('Rice')
        ->and($context['demands'][1]['crop'])->toBe('Calamansi')
        ->and($context['demands'][0])->toHaveKeys(['crop', 'quantity_kg', 'target_price_per_kg', 'needed_by']);
});

it('maps recent farm crops to previous slugs', function () {
    $plot = contextPlot();
    $farmerId = $plot->farm->user_id;
    $rec = CropRecommendation::create([
        'plot_id' => $plot->id,
        'crop_name' => 'Tomatoes',
        'confidence_score' => 90,
        'reasoning' => 'Good fit',
        'status' => RecommendationStatus::ACCEPTED,
    ]);

    ForwardContract::create([
        'farmer_id' => $farmerId,
        'crop_recommendation_id' => $rec->id,
        'title' => 'Old deal',
        'crop_name' => 'Tomatoes',
        'quantity_kg' => 100,
        'price_per_kg' => 20,
        'total_price' => 2000,
        'estimated_harvest_date' => now()->addDays(60)->toDateString(),
        'expiry_date' => now()->addDays(90)->toDateString(),
        'status' => ContractStatus::SOLD,
    ]);

    $context = app(BuildAnalysisContextAction::class)->execute($plot, null);

    expect($context['previous_crops'])->toContain('tomato');
});

it('omits empty market and history sections', function () {
    $context = app(BuildAnalysisContextAction::class)->execute(contextPlot(), null);

    expect($context)->not->toHaveKey('demands')
        ->and($context)->not->toHaveKey('previous_crops');
});

it('handles plots without a farm', function () {
    $plot = new Plot;
    $plot->name = 'Orphan';
    $plot->soil_type = 'loamy';
    $plot->calculated_area = 1.0;

    $context = app(BuildAnalysisContextAction::class)->execute($plot, null);

    expect($context)->not->toHaveKey('previous_crops')
        ->and($context['season'])->toBe(CropTaxonomy::currentSeason());
});

it('excludes hidden demands from fresh analysis context', function () {
    makeDemand('Rice', 9000);
    makeDemand('Hidden Corn', 12000);

    $hidden = CropDemand::where('crop_name', 'Hidden Corn')->firstOrFail();
    $admin = User::factory()->create(['role' => 'admin']);

    app(ModerateMarketplaceContentAction::class)->execute(
        $admin, ReportTargetType::DEMAND, (string) $hidden->id, true, 'suspected fraud'
    );

    $context = app(BuildAnalysisContextAction::class)->execute(contextPlot(), null);

    expect($context['demands'])->toHaveCount(1)
        ->and($context['demands'][0]['crop'])->toBe('Rice');
});
