<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Enums;

enum ReportStatus: string
{
    case NONE = 'none';
    case GENERATING = 'generating';
    case READY = 'ready';
    case FAILED = 'failed';
}
