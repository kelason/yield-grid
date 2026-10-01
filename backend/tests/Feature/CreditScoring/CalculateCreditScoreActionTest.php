<?php

use App\Domain\CreditScoring\Actions\CalculateCreditScoreAction;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ReverseMarketplace\ReverseMarketplaceHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function creditScoringPlot(Farm $farm, int $count = 1, bool $complete = true): void
{
    for ($i = 0; $i < $count; $i++) {
        Plot::create([
            'farm_id' => $farm->id,
            'name' => "Plot {$farm->id}-{$i}",
            'polygon' => $complete ? '{"type": "Polygon", "coordinates": []}' : null,
            'soil_type' => $complete ? 'clay' : null,
            'calculated_area' => 1.5,
        ]);
    }
}

function creditScoringFarm(User $farmer, int $plots = 1, bool $complete = true): Farm
{
    $farm = Farm::create(['user_id' => $farmer->id, 'name' => 'Test Farm']);

    creditScoringPlot($farm, $plots, $complete);

    return $farm;
}

function creditScoringRecommendation(Plot $plot, RecommendationStatus $status = RecommendationStatus::ACCEPTED): CropRecommendation
{
    return CropRecommendation::create([
        'plot_id' => $plot->id,
        'crop_name' => 'Rice',
        'confidence_score' => 90,
        'reasoning' => 'Good fit',
        'projected_yield' => '5 tons',
        'status' => $status,
    ]);
}

function creditScoringContract(User $farmer, int $recommendationId, ContractStatus $status = ContractStatus::SOLD): ForwardContract
{
    return ForwardContract::factory()->create([
        'farmer_id' => $farmer->id,
        'crop_recommendation_id' => $recommendationId,
        'status' => $status,
    ]);
}

function creditScoringPurchase(ForwardContract $contract, float $amountPaid): Purchase
{
    return Purchase::factory()->completed()->create([
        'forward_contract_id' => $contract->id,
        'amount_paid' => $amountPaid,
        'purchased_at' => now(),
    ]);
}

it('scores a brand-new farmer at zero with the new-farmer tier', function () {
    $farmer = User::factory()->farmer()->unverified()->create([
        'phone' => null,
        'avatar_url' => null,
    ]);

    $score = app(CalculateCreditScoreAction::class)->execute($farmer);

    expect($score->overallScore)->toBe(0)
        ->and($score->tier)->toBe(ScoreTier::NEW_FARMER)
        ->and($score->breakdown->toArray())->toBe([
            'plot_activity' => 0,
            'recommendation_adherence' => 0,
            'contract_fulfillment' => 0,
            'offer_reliability' => 0,
            'transaction_volume' => 0,
            'platform_tenure' => 0,
        ]);

    $snapshot = CreditScoreSnapshot::where('user_id', $farmer->id)->firstOrFail();

    expect($snapshot->overall_score)->toBe(0)
        ->and($snapshot->tier)->toBe(ScoreTier::NEW_FARMER)
        ->and($snapshot->dimension_scores)->toHaveCount(6)
        ->and($snapshot->raw_metrics)->toHaveKey('active_plots');
});

it('rewards verification and profile completeness in the tenure dimension', function () {
    $farmer = User::factory()->farmer()->create([
        'email_verified_at' => now(),
        'phone' => '+639171234567',
        'avatar_url' => 'https://example.com/avatar.png',
        'created_at' => now()->subDays(400),
    ]);
    ReverseMarketplaceHelper::makeAddress($farmer);

    $score = app(CalculateCreditScoreAction::class)->execute($farmer);

    expect($score->breakdown->platformTenure)->toBe(100)
        ->and($score->overallScore)->toBe(10)
        ->and($score->tier)->toBe(ScoreTier::NEW_FARMER);
});

it('scores a high-activity farmer as excellent', function () {
    $farmer = User::factory()->farmer()->create([
        'email_verified_at' => now(),
        'phone' => '+639171234567',
        'avatar_url' => 'https://example.com/avatar.png',
    ]);
    ReverseMarketplaceHelper::makeAddress($farmer);

    $farm = creditScoringFarm($farmer, 3);
    $plot = $farm->plots()->firstOrFail();
    $recOne = creditScoringRecommendation($plot);
    $recTwo = creditScoringRecommendation($plot);

    $contracts = [];
    for ($i = 0; $i < 20; $i++) {
        $contracts[] = creditScoringContract($farmer, $i % 2 === 0 ? $recOne->id : $recTwo->id);
    }

    foreach ($contracts as $contract) {
        creditScoringPurchase($contract, 25000.00);
    }

    $buyer = User::factory()->buyer()->create();
    $demand = ReverseMarketplaceHelper::makeDemand($buyer);
    ReverseMarketplaceHelper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::ACCEPTED]);
    for ($i = 0; $i < 4; $i++) {
        ReverseMarketplaceHelper::makeOffer($demand, $farmer, ['status' => DemandOfferStatus::COMPLETED]);
    }

    $score = app(CalculateCreditScoreAction::class)->execute($farmer);

    expect($score->breakdown->plotActivity)->toBe(100)
        ->and($score->breakdown->recommendationAdherence)->toBe(100)
        ->and($score->breakdown->contractFulfillment)->toBe(100)
        ->and($score->breakdown->transactionVolume)->toBe(100)
        ->and($score->overallScore)->toBe(90)
        ->and($score->tier)->toBe(ScoreTier::EXCELLENT);
});

it('penalizes cancelled contracts', function () {
    $cleanFarmer = User::factory()->farmer()->create();
    $cleanFarm = creditScoringFarm($cleanFarmer);
    $cleanRec = creditScoringRecommendation($cleanFarm->plots()->firstOrFail());
    creditScoringContract($cleanFarmer, $cleanRec->id);
    creditScoringContract($cleanFarmer, $cleanRec->id);

    $messyFarmer = User::factory()->farmer()->create();
    $messyFarm = creditScoringFarm($messyFarmer);
    $messyRec = creditScoringRecommendation($messyFarm->plots()->firstOrFail());
    creditScoringContract($messyFarmer, $messyRec->id);
    creditScoringContract($messyFarmer, $messyRec->id);
    creditScoringContract($messyFarmer, $messyRec->id, ContractStatus::CANCELLED);
    creditScoringContract($messyFarmer, $messyRec->id, ContractStatus::CANCELLED);

    $clean = app(CalculateCreditScoreAction::class)->execute($cleanFarmer);
    $messy = app(CalculateCreditScoreAction::class)->execute($messyFarmer);

    expect($clean->breakdown->contractFulfillment)->toBe(80)
        ->and($messy->breakdown->contractFulfillment)->toBe(35)
        ->and($messy->overallScore)->toBeLessThan($clean->overallScore);
});

it('scores zero for recommendations below the minimum count', function () {
    $farmer = User::factory()->farmer()->create();
    $farm = creditScoringFarm($farmer);
    creditScoringRecommendation($farm->plots()->firstOrFail());

    $score = app(CalculateCreditScoreAction::class)->execute($farmer);

    expect($score->breakdown->recommendationAdherence)->toBe(0);
});

it('scores partial plot data proportionally', function () {
    $farmer = User::factory()->farmer()->create();
    creditScoringFarm($farmer, 1, false);

    $score = app(CalculateCreditScoreAction::class)->execute($farmer);

    expect($score->breakdown->plotActivity)->toBe(13);
});

it('generates improvement tips for weak dimensions', function () {
    $farmer = User::factory()->farmer()->unverified()->create(['phone' => null]);

    $score = app(CalculateCreditScoreAction::class)->execute($farmer);

    $messages = implode(' ', array_column($score->improvementTips, 'message'));

    expect($score->improvementTips)->not->toBeEmpty()
        ->and($messages)->toContain('plot')
        ->and(array_column($score->improvementTips, 'dimension'))->toContain('plot_activity');
});

it('recalculates every farmer via the daily cron command', function () {
    $farmers = User::factory()->farmer()->count(3)->create();
    User::factory()->buyer()->create();

    $this->artisan('credit-scoring:recalculate-all')->assertSuccessful();

    foreach ($farmers as $farmer) {
        expect(CreditScoreSnapshot::where('user_id', $farmer->id)->count())->toBe(1);
    }
    expect(CreditScoreSnapshot::count())->toBe(3);
});
