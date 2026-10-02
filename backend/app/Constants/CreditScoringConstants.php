<?php

declare(strict_types=1);

namespace App\Constants;

final class CreditScoringConstants
{
    // Dimension weights (must sum to 1.0)
    public const WEIGHT_PLOT_ACTIVITY = 0.15;

    public const WEIGHT_RECOMMENDATION = 0.15;

    public const WEIGHT_CONTRACT_FULFILLMENT = 0.25;

    public const WEIGHT_OFFER_RELIABILITY = 0.20;

    public const WEIGHT_TRANSACTION_VOLUME = 0.15;

    public const WEIGHT_PLATFORM_TENURE = 0.10;

    // Scoring caps
    public const EXPECTED_PLOTS = 3;

    public const MIN_RECOMMENDATIONS_FOR_SCORE = 2;

    public const CONTRACT_VOLUME_CAP = 10;

    public const TRANSACTION_VALUE_CAP_PHP = 500000;

    public const TRANSACTION_COUNT_CAP = 20;

    public const TENURE_CAP_DAYS = 365;

    // Tier thresholds
    public const TIER_EXCELLENT_MIN = 80;

    public const TIER_GOOD_MIN = 60;

    public const TIER_FAIR_MIN = 40;

    public const TIER_DEVELOPING_MIN = 20;

    // Max dimension score
    public const MAX_DIMENSION_SCORE = 100;

    // Report settings
    public const REPORT_EXPIRY_DAYS = 30;

    public const REPORT_TOKEN_LENGTH = 64;

    public const REPORT_GENERATION_TIMEOUT_SECONDS = 60;

    // Rate limits
    public const SCORE_CALCULATE_THROTTLE_PER_HOUR = 10;

    public const REPORT_GENERATE_THROTTLE_PER_DAY = 3;

    // Score history
    public const SCORE_HISTORY_MAX_RESULTS = 50;

    // Penalty multipliers
    public const CANCELLATION_PENALTY_MULTIPLIER = 30;

    public const WITHDRAWAL_PENALTY_MULTIPLIER = 30;
}
