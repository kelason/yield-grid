<?php

declare(strict_types=1);

namespace App\Constants;

final class AuthConstants
{
    public const int LOGIN_PASSWORD_MIN_LENGTH = 1;

    public const int NAME_MAX_LENGTH = 255;

    public const int EMAIL_MAX_LENGTH = 255;

    public const int PASSWORD_MAX_LENGTH = 255;

    public const int RESET_TOKEN_MAX_LENGTH = 255;

    public const int SANCTUM_TOKEN_CAP_MINUTES = 43200;

    public const int VERIFICATION_LINK_EXPIRE_MINUTES = 1440;
}
