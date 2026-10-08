<?php

declare(strict_types=1);

namespace App\Constants;

final class ContactConstants
{
    public const int NAME_MAX_LENGTH = 255;

    public const int EMAIL_MAX_LENGTH = 255;

    public const int SUBJECT_MAX_LENGTH = 255;

    public const int MESSAGE_MAX_LENGTH = 2000;

    public const int REPLY_BODY_MAX_LENGTH = 5000;

    public const int REPLY_MAX_ATTEMPTS = 3;

    /** @var array<int, int> */
    public const array REPLY_BACKOFF_SECONDS = [60, 300];

    public const int REPLY_TIMEOUT_SECONDS = 60;

    public const int REPLY_LEASE_SECONDS = 300;

    public const string REPLY_ERROR_TRANSPORT_FAILED = 'transport_failed';

    public const string REPLY_EMAIL_SUBJECT = 'Re: Your YieldGrid inquiry';

    public const string REPLY_STALE_SENDING_WARNING = 'The previous attempt may already have been delivered; retrying can send a duplicate.';
}
