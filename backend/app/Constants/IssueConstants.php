<?php

declare(strict_types=1);

namespace App\Constants;

final class IssueConstants
{
    public const int SUBJECT_MAX_LENGTH = 150;

    public const int DESCRIPTION_MAX_LENGTH = 5000;

    public const int PAGE_PATH_MAX_LENGTH = 255;

    public const int RESOLUTION_MAX_LENGTH = 2000;

    public const int ISSUES_PER_MINUTE = 5;

    public const int ISSUES_PER_DAY = 20;

    public const string ISSUE_MINUTE_LIMITER = 'issue-create-minute';

    public const string ISSUE_DAILY_LIMITER = 'issue-create-daily';

    public const int EXPECTED_VERSION_MAX = 2147483647;
}
