<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Actions;

use App\Constants\CreditScoringConstants;
use App\Domain\CreditScoring\DTOs\CreditScoreData;
use App\Domain\CreditScoring\DTOs\ScoreBreakdownData;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Support\Carbon;

final class CalculateCreditScoreAction
{
    public function __construct(
        private readonly GenerateImprovementTipsAction $tipsAction,
    ) {}

    /**
     * Calculate the Farmer Trust Score for a given user and persist a snapshot.
     */
    public function execute(User $user): CreditScoreData
    {
        $metrics = $this->gatherMetrics($user);
        $breakdown = $this->scoreAllDimensions($metrics);
        $overall = $this->computeOverall($breakdown);
        $tier = ScoreTier::fromScore($overall);
        $tips = $this->tipsAction->execute($breakdown, $metrics);

        CreditScoreSnapshot::create([
            'user_id' => $user->id,
            'overall_score' => $overall,
            'tier' => $tier,
            'dimension_scores' => $breakdown->toArray(),
            'raw_metrics' => $metrics,
        ]);

        return new CreditScoreData(
            overallScore: $overall,
            tier: $tier,
            breakdown: $breakdown,
            rawMetrics: $metrics,
            improvementTips: $tips,
        );
    }

    /**
     * Gather all raw metrics from across bounded contexts using efficient aggregate queries.
     *
     * @return array<string, mixed>
     */
    private function gatherMetrics(User $user): array
    {
        $userId = $user->id;
        $now = Carbon::now();

        // Farming context
        $farmIds = Farm::where('user_id', $userId)->pluck('id');
        $plots = Plot::whereIn('farm_id', $farmIds);
        $activePlots = $plots->count();
        $plotsWithPolygon = (clone $plots)->whereNotNull('polygon')->count();
        $plotsWithSoilType = (clone $plots)->whereNotNull('soil_type')->count();
        $plotIds = Plot::whereIn('farm_id', $farmIds)->pluck('id');

        // CropRecommendation context
        $totalRecommendations = CropRecommendation::whereIn('plot_id', $plotIds)->count();
        $acceptedRecommendations = CropRecommendation::whereIn('plot_id', $plotIds)
            ->where('status', RecommendationStatus::ACCEPTED)
            ->count();
        $publishedToContract = ForwardContract::where('farmer_id', $userId)
            ->whereNotNull('crop_recommendation_id')
            ->count();

        // Marketplace context — contracts
        $totalContracts = ForwardContract::where('farmer_id', $userId)->count();
        $soldContracts = ForwardContract::where('farmer_id', $userId)
            ->where('status', ContractStatus::SOLD)
            ->count();
        $cancelledContracts = ForwardContract::where('farmer_id', $userId)
            ->where('status', ContractStatus::CANCELLED)
            ->count();
        $expiredContracts = ForwardContract::where('farmer_id', $userId)
            ->where('status', ContractStatus::EXPIRED)
            ->count();

        // Marketplace context — demand offers
        $totalOffers = CropDemandOffer::where('farmer_id', $userId)->count();
        $acceptedOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::ACCEPTED)
            ->count();
        $completedOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::COMPLETED)
            ->count();
        $withdrawnOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::WITHDRAWN)
            ->count();
        $cancelledOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::CANCELLED)
            ->count();
        $deliveredOffers = CropDemandOffer::where('farmer_id', $userId)
            ->where('status', DemandOfferStatus::DELIVERED)
            ->count();
        // Offers that reached a "successful" lifecycle stage (accepted+)
        $offersWithDelivery = CropDemandOffer::where('farmer_id', $userId)
            ->whereIn('status', [
                DemandOfferStatus::DELIVERED,
                DemandOfferStatus::COMPLETED,
            ])
            ->count();

        // Transaction volume — purchases where this farmer was the seller
        $farmerPurchases = Purchase::where('payment_status', PaymentStatus::COMPLETED)
            ->whereHas('contract', fn ($q) => $q->where('farmer_id', $userId))
            ->orWhereHas('harvestListing', fn ($q) => $q->where('farmer_id', $userId))
            ->orWhereHas('demandOffer', fn ($q) => $q->where('farmer_id', $userId));

        $totalTransactionValue = (float) (clone $farmerPurchases)->sum('amount_paid');
        $transactionCount = (clone $farmerPurchases)->count();

        // Calculate active months: months where at least one purchase was paid
        $firstPurchaseDate = (clone $farmerPurchases)->min('purchased_at');
        $activeMonths = 0;
        if ($firstPurchaseDate !== null) {
            $activeMonths = (int) (clone $farmerPurchases)
                ->selectRaw('DISTINCT EXTRACT(YEAR FROM purchased_at) * 12 + EXTRACT(MONTH FROM purchased_at) AS ym')
                ->count();
        }

        // Platform tenure
        $accountAgeDays = (int) $user->created_at->diffInDays($now);
        $accountAgeMonths = max(1, (int) $user->created_at->diffInMonths($now));

        return [
            // Plot activity
            'active_plots' => $activePlots,
            'plots_with_polygon' => $plotsWithPolygon,
            'plots_with_soil_type' => $plotsWithSoilType,
            'has_recommendations' => $totalRecommendations > 0,

            // Recommendations
            'total_recommendations' => $totalRecommendations,
            'accepted_recommendations' => $acceptedRecommendations,
            'published_to_contract' => $publishedToContract,

            // Contracts
            'total_contracts' => $totalContracts,
            'sold_contracts' => $soldContracts,
            'cancelled_contracts' => $cancelledContracts,
            'expired_contracts' => $expiredContracts,

            // Offers
            'total_offers' => $totalOffers,
            'accepted_offers' => $acceptedOffers,
            'completed_offers' => $completedOffers,
            'withdrawn_offers' => $withdrawnOffers,
            'cancelled_offers' => $cancelledOffers,
            'delivered_offers' => $deliveredOffers,
            'offers_with_delivery' => $offersWithDelivery,

            // Volume
            'total_transaction_value' => $totalTransactionValue,
            'transaction_count' => $transactionCount,
            'active_months' => $activeMonths,

            // Tenure
            'account_age_days' => $accountAgeDays,
            'account_age_months' => $accountAgeMonths,
            'email_verified' => $user->hasVerifiedEmail(),
            'has_phone' => $user->phone !== null && $user->phone !== '',
            'has_address' => $user->addresses()->exists(),
            'has_avatar' => $user->avatar_url !== null && $user->avatar_url !== '',
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scoreAllDimensions(array $metrics): ScoreBreakdownData
    {
        return new ScoreBreakdownData(
            plotActivity: $this->scorePlotActivity($metrics),
            recommendationAdherence: $this->scoreRecommendations($metrics),
            contractFulfillment: $this->scoreContracts($metrics),
            offerReliability: $this->scoreOffers($metrics),
            transactionVolume: $this->scoreVolume($metrics),
            platformTenure: $this->scoreTenure($metrics),
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scorePlotActivity(array $metrics): int
    {
        $activePlots = (int) $metrics['active_plots'];

        if ($activePlots === 0) {
            return 0;
        }

        $plotCountScore = min($activePlots, CreditScoringConstants::EXPECTED_PLOTS)
            / CreditScoringConstants::EXPECTED_PLOTS * 40;

        $polygonScore = (int) $metrics['plots_with_polygon'] / $activePlots * 30;
        $soilTypeScore = (int) $metrics['plots_with_soil_type'] / $activePlots * 20;
        $recommendationBonus = $metrics['has_recommendations'] ? 10 : 0;

        return $this->clampScore((int) round($plotCountScore + $polygonScore + $soilTypeScore + $recommendationBonus));
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scoreRecommendations(array $metrics): int
    {
        $total = (int) $metrics['total_recommendations'];

        if ($total < CreditScoringConstants::MIN_RECOMMENDATIONS_FOR_SCORE) {
            return 0;
        }

        $accepted = (int) $metrics['accepted_recommendations'];
        $published = (int) $metrics['published_to_contract'];

        $acceptanceScore = $accepted / $total * 70;
        $followThroughScore = $accepted > 0 ? $published / $accepted * 30 : 0;

        return $this->clampScore((int) round($acceptanceScore + $followThroughScore));
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scoreContracts(array $metrics): int
    {
        $total = (int) $metrics['total_contracts'];

        if ($total === 0) {
            return 0;
        }

        $sold = (int) $metrics['sold_contracts'];
        $cancelled = (int) $metrics['cancelled_contracts'];

        $completionScore = $sold / $total * 60;
        $volumeBonus = min($sold, CreditScoringConstants::CONTRACT_VOLUME_CAP)
            / CreditScoringConstants::CONTRACT_VOLUME_CAP * 25;

        $cancelledRatio = $cancelled / $total;
        $cleanRecordScore = $cancelled === 0
            ? 15
            : max(0, 15 - $cancelledRatio * CreditScoringConstants::CANCELLATION_PENALTY_MULTIPLIER);

        return $this->clampScore((int) round($completionScore + $volumeBonus + $cleanRecordScore));
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scoreOffers(array $metrics): int
    {
        $total = (int) $metrics['total_offers'];

        if ($total === 0) {
            return 0;
        }

        $accepted = (int) $metrics['accepted_offers'];
        $completed = (int) $metrics['completed_offers'];
        $withdrawn = (int) $metrics['withdrawn_offers'];
        $delivered = (int) $metrics['offers_with_delivery'];

        $acceptanceScore = $accepted / $total * 30;

        $deliveryCompletionScore = $accepted > 0
            ? ($completed + $delivered) / max(1, $accepted + $completed + $delivered) * 40
            : 0;

        $withdrawnRatio = $withdrawn / $total;
        $reliabilityScore = $withdrawn === 0
            ? 15
            : max(0, 15 - $withdrawnRatio * CreditScoringConstants::WITHDRAWAL_PENALTY_MULTIPLIER);

        // On-time delivery approximation: completed vs total accepted lifecycle
        $onTimeRatio = $accepted > 0
            ? min(1, $completed / max(1, $accepted)) : 0;
        $timelinessScore = $onTimeRatio * 15;

        return $this->clampScore((int) round(
            $acceptanceScore + $deliveryCompletionScore + $reliabilityScore + $timelinessScore
        ));
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scoreVolume(array $metrics): int
    {
        $totalValue = (float) $metrics['total_transaction_value'];
        $count = (int) $metrics['transaction_count'];
        $activeMonths = (int) $metrics['active_months'];
        $accountAgeMonths = (int) $metrics['account_age_months'];

        $valueScore = min($totalValue, CreditScoringConstants::TRANSACTION_VALUE_CAP_PHP)
            / CreditScoringConstants::TRANSACTION_VALUE_CAP_PHP * 50;

        $frequencyScore = min($count, CreditScoringConstants::TRANSACTION_COUNT_CAP)
            / CreditScoringConstants::TRANSACTION_COUNT_CAP * 30;

        $consistencyScore = $accountAgeMonths > 0
            ? min(1, $activeMonths / $accountAgeMonths) * 20
            : 0;

        return $this->clampScore((int) round($valueScore + $frequencyScore + $consistencyScore));
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function scoreTenure(array $metrics): int
    {
        $ageDays = (int) $metrics['account_age_days'];

        $ageScore = min($ageDays, CreditScoringConstants::TENURE_CAP_DAYS)
            / CreditScoringConstants::TENURE_CAP_DAYS * 40;

        $emailScore = $metrics['email_verified'] ? 20 : 0;
        $phoneScore = $metrics['has_phone'] ? 15 : 0;
        $addressScore = $metrics['has_address'] ? 15 : 0;
        $avatarScore = $metrics['has_avatar'] ? 10 : 0;

        return $this->clampScore((int) round($ageScore + $emailScore + $phoneScore + $addressScore + $avatarScore));
    }

    private function computeOverall(ScoreBreakdownData $breakdown): int
    {
        $weighted = $breakdown->plotActivity * CreditScoringConstants::WEIGHT_PLOT_ACTIVITY
            + $breakdown->recommendationAdherence * CreditScoringConstants::WEIGHT_RECOMMENDATION
            + $breakdown->contractFulfillment * CreditScoringConstants::WEIGHT_CONTRACT_FULFILLMENT
            + $breakdown->offerReliability * CreditScoringConstants::WEIGHT_OFFER_RELIABILITY
            + $breakdown->transactionVolume * CreditScoringConstants::WEIGHT_TRANSACTION_VOLUME
            + $breakdown->platformTenure * CreditScoringConstants::WEIGHT_PLATFORM_TENURE;

        return $this->clampScore((int) round($weighted));
    }

    private function clampScore(int $score): int
    {
        return max(0, min(CreditScoringConstants::MAX_DIMENSION_SCORE, $score));
    }
}
