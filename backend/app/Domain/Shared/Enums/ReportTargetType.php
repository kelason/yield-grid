<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ReportTargetType: string
{
    case THREAD = 'thread';
    case REPLY = 'reply';
    case CONTRACT = 'contract';
    case LISTING = 'listing';
    case DEMAND = 'demand';
}
