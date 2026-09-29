<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum DemandStatus: string
{
    case OPEN = 'open';
    case FULLY_ALLOCATED = 'fully_allocated';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
