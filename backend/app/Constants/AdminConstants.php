<?php

declare(strict_types=1);

namespace App\Constants;

final class AdminConstants
{
    public const int ADMIN_PASSWORD_MIN_LENGTH = 12;

    public const int ADMIN_SEARCH_MAX_LENGTH = 100;

    public const int SUSPENSION_REASON_MAX_LENGTH = 500;

    public const int ADMIN_TOKEN_EXPIRATION_MINUTES = 120;

    public const int ADMIN_WRITE_THROTTLE_MAX_ATTEMPTS = 30;

    public const int ADMIN_WRITE_THROTTLE_DECAY_MINUTES = 1;

    public const string SUSPENDED_MESSAGE = 'This account is suspended. Contact support for assistance.';

    public const string SUSPENDED_CODE = 'account_suspended';

    public const string CACHE_CONTROL_NO_STORE = 'private, no-store';

    public const string ADMIN_USER_ROLE_MEMBERS = 'members';
}
