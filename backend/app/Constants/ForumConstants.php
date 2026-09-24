<?php

declare(strict_types=1);

namespace App\Constants;

final class ForumConstants
{
    public const int THREADS_PER_PAGE = 15;

    public const int REPLIES_PER_PAGE = 20;

    public const int MAX_TAGS_PER_THREAD = 5;

    public const int TITLE_MIN_LENGTH = 10;

    public const int TITLE_MAX_LENGTH = 255;

    public const int BODY_MIN_LENGTH = 20;

    public const int BODY_MAX_LENGTH = 10000;

    public const int REPLY_MIN_LENGTH = 5;

    public const int REPLY_MAX_LENGTH = 5000;
}
