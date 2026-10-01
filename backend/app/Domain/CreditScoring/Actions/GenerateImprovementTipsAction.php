<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Actions;

use App\Constants\CreditScoringConstants;
use App\Domain\CreditScoring\DTOs\ScoreBreakdownData;

final class GenerateImprovementTipsAction
{
    /**
     * Build dimension-tagged improvement tips from a score breakdown and its metrics.
     *
     * Shared by fresh calculations and stored snapshots so both serve the same tips.
     *
     * @param  array<string, mixed>  $metrics
     * @return list<array{dimension: string, message: string}>
     */
    public function execute(ScoreBreakdownData $breakdown, array $metrics): array
    {
        // Stored snapshots may carry partial metric sets — missing values degrade
        // to zero/false so every weak dimension still gets its tip.
        $metrics += [
            'active_plots' => 0,
            'plots_with_polygon' => 0,
            'plots_with_soil_type' => 0,
            'total_recommendations' => 0,
            'accepted_recommendations' => 0,
            'total_contracts' => 0,
            'cancelled_contracts' => 0,
            'total_offers' => 0,
            'withdrawn_offers' => 0,
            'email_verified' => false,
            'has_phone' => false,
            'has_address' => false,
        ];

        $tips = [];

        // Plot activity tips
        if ($breakdown->plotActivity < CreditScoringConstants::TIER_GOOD_MIN) {
            if ((int) $metrics['active_plots'] === 0) {
                $tips[] = $this->tip('plot_activity', 'Register your farm and add at least one plot to start building your score.');
            } elseif ((int) $metrics['plots_with_polygon'] < (int) $metrics['active_plots']) {
                $tips[] = $this->tip('plot_activity', 'Complete polygon boundaries for all your plots to improve your Plot Activity score.');
            }
            if ((int) $metrics['plots_with_soil_type'] < (int) $metrics['active_plots']) {
                $tips[] = $this->tip('plot_activity', 'Add soil type information to your plots for a more complete profile.');
            }
        }

        // Recommendation tips
        if ($breakdown->recommendationAdherence < CreditScoringConstants::TIER_GOOD_MIN) {
            if ((int) $metrics['total_recommendations'] < CreditScoringConstants::MIN_RECOMMENDATIONS_FOR_SCORE) {
                $tips[] = $this->tip('recommendation_adherence', 'Request AI crop recommendations for your plots to unlock this score dimension.');
            } elseif ((int) $metrics['accepted_recommendations'] < (int) $metrics['total_recommendations']) {
                $tips[] = $this->tip('recommendation_adherence', 'Accept and follow AI crop recommendations to improve your adherence score.');
            }
        }

        // Contract tips
        if ($breakdown->contractFulfillment < CreditScoringConstants::TIER_GOOD_MIN) {
            if ((int) $metrics['total_contracts'] === 0) {
                $tips[] = $this->tip('contract_fulfillment', 'Publish your first forward contract to start building your marketplace track record.');
            } elseif ((int) $metrics['cancelled_contracts'] > 0) {
                $tips[] = $this->tip('contract_fulfillment', 'Avoid cancelling contracts to maintain a clean fulfillment record.');
            }
        }

        // Offer tips
        if ($breakdown->offerReliability < CreditScoringConstants::TIER_GOOD_MIN) {
            if ((int) $metrics['total_offers'] === 0) {
                $tips[] = $this->tip('offer_reliability', 'Submit offers on buyer demands to build your offer reliability score.');
            } elseif ((int) $metrics['withdrawn_offers'] > 0) {
                $tips[] = $this->tip('offer_reliability', 'Minimize offer withdrawals — complete your commitments for a higher reliability score.');
            }
        }

        // Volume tips
        if ($breakdown->transactionVolume < CreditScoringConstants::TIER_GOOD_MIN) {
            $tips[] = $this->tip('transaction_volume', 'Complete more marketplace transactions to increase your volume score.');
        }

        // Tenure tips
        if ($breakdown->platformTenure < CreditScoringConstants::TIER_GOOD_MIN) {
            if (! $metrics['email_verified']) {
                $tips[] = $this->tip('platform_tenure', 'Verify your email address for an immediate score boost.');
            }
            if (! $metrics['has_phone']) {
                $tips[] = $this->tip('platform_tenure', 'Add your phone number to your profile.');
            }
            if (! $metrics['has_address']) {
                $tips[] = $this->tip('platform_tenure', 'Register a marketplace delivery address.');
            }
        }

        return $tips;
    }

    /**
     * @return array{dimension: string, message: string}
     */
    private function tip(string $dimension, string $message): array
    {
        return ['dimension' => $dimension, 'message' => $message];
    }
}
