<?php

declare(strict_types=1);

namespace App\Constants;

final class InsuranceConstants
{
    /**
     * Sentinel region code for country-wide fallback rows.
     */
    public const NATIONAL_REGION_CODE = 'NATIONAL';

    /**
     * 2026 PCIC multi-peril cover for rice/corn total loss, per hectare in PHP.
     * Informational estimate only — the farmer's CIC states the binding amount.
     */
    public const COVERAGE_PER_HECTARE_PHP = 25000.00;

    /**
     * Written Notice of Loss must reach PCIC within this many calendar days of loss.
     */
    public const NOTICE_OF_LOSS_DEADLINE_DAYS = 10;

    /**
     * Remind farmers this many days before the planting window opens.
     */
    public const ENROLLMENT_REMINDER_LEAD_DAYS = 30;

    /**
     * Remind farmers this many days before policy expiry.
     */
    public const RENEWAL_REMINDER_LEAD_DAYS = 30;

    /**
     * Nudge farmers with claims idle longer than this many days.
     */
    public const CLAIM_FOLLOWUP_AFTER_DAYS = 14;

    public const RSBSA_NUMBER_MAX_LENGTH = 30;

    public const CIC_NUMBER_MAX_LENGTH = 30;

    public const NOTES_MAX_LENGTH = 1000;

    public const CLAIM_DESCRIPTION_MAX_LENGTH = 5000;

    public const SEASON_YEAR_MIN = 2020;

    public const SEASON_YEAR_MAX = 2100;

    public const COVERAGE_AMOUNT_MAX = 99999999.99;

    public const PACK_TOKEN_LENGTH = 64;

    public const PACK_EXPIRY_DAYS = 30;

    public const PACK_GENERATION_TIMEOUT_SECONDS = 60;

    public const PACK_GENERATE_THROTTLE_PER_DAY = 3;

    public const PACK_DOWNLOAD_THROTTLE_PER_DAY = 3;

    public const NOTICE_OF_LOSS_REMINDER_THRESHOLD_DAYS = 3;

    public const REMINDERS_MAX_RESULTS = 20;
}
