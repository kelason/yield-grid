<?php

declare(strict_types=1);

namespace App\Domain\Community\Enums;

enum ThreadSortOption: string
{
    case LATEST = 'latest';
    case MOST_VOTED = 'most_voted';
    case MOST_REPLIED = 'most_replied';
    case UNANSWERED = 'unanswered';
}
