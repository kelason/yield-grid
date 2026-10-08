<?php

declare(strict_types=1);

namespace App\Constants;

final class ReportingConstants
{
    public const int DESCRIPTION_MAX_LENGTH = 1000;

    public const int SNAPSHOT_FIELD_MAX_LENGTH = 500;

    public const int REPORTS_PER_MINUTE = 5;

    public const int REPORTS_PER_DAY = 20;

    public const string REPORT_MINUTE_LIMITER = 'report-create-minute';

    public const string REPORT_DAILY_LIMITER = 'report-create-daily';

    public const string SELF_REPORT_MESSAGE = 'You cannot report your own content.';

    public const string OTHER_REASON_DESCRIPTION_MESSAGE = 'A description is required when the reason is other.';
}
