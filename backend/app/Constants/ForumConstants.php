<?php

declare(strict_types=1);

namespace App\Constants;

final class ForumConstants
{
    public const int SEARCH_MIN_LENGTH = 0;

    public const int SEARCH_MAX_LENGTH = 100;

    public const int THREADS_PER_PAGE = 15;

    public const int REPLIES_PER_PAGE = 20;

    public const int MAX_TAGS_PER_THREAD = 5;

    public const int TITLE_MIN_LENGTH = 10;

    public const int TITLE_MAX_LENGTH = 100;

    public const int BODY_MIN_LENGTH = 20;

    public const int BODY_MAX_LENGTH = 5000;

    public const int REPLY_MIN_LENGTH = 5;

    public const int REPLY_MAX_LENGTH = 5000;

    public const int REPORT_DESCRIPTION_MAX_LENGTH = 1000;

    public const int ATTACHMENT_MAX_SIZE_KB = 10240;

    public const string ATTACHMENT_ALLOWED_MIMES = 'jpeg,png,jpg,gif,webp';

    /** @var list<string> */
    public const array REPORTABLE_TYPES = ['thread', 'reply'];

    /** @var list<string> */
    public const array ATTACHABLE_TYPES = ['thread', 'reply'];

    public const int VOTE_UP = 1;

    public const int VOTE_DOWN = -1;

    /** @var list<int> */
    public const array VOTE_VALUES = [self::VOTE_UP, self::VOTE_DOWN];
}
