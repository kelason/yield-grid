<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Actions;

use App\Constants\CreditScoringConstants;
use App\Domain\CreditScoring\DTOs\CreditScoreData;
use App\Domain\CreditScoring\DTOs\ScoreBreakdownData;
use App\Domain\CreditScoring\Enums\ScoreTier;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use Domain\Users\Models\User;

final class CalculateCreditScoreAction
{
    public function __construct(
        private readonly GatherCreditScoreMetricsAction $metricsAction,
        private readonly GenerateImprovementTipsAction $tipsAction,
    ) {}

    /**
     * Calculate the Farmer Trust Score for a given user and persist a snapshot.
     */
    public function execute(User $user): CreditScoreData
    {
        $metrics = $this->metricsAction->execute($user);
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
        $timelinessScore = $this->scoreOfferTimeliness($accepted, $completed);

        return $this->clampScore((int) round(
            $acceptanceScore + $deliveryCompletionScore + $reliabilityScore + $timelinessScore
        ));
    }

    /**
     * On-time delivery approximation: completed vs total accepted lifecycle.
     */
    private function scoreOfferTimeliness(int $accepted, int $completed): float
    {
        $onTimeRatio = $accepted > 0 ? min(1, $completed / max(1, $accepted)) : 0;

        return $onTimeRatio * 15;
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
