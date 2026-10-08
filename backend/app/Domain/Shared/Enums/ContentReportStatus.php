<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ContentReportStatus: string
{
    case OPEN = 'open';
    case REVIEWING = 'reviewing';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}
