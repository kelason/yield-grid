<?php

declare(strict_types=1);

namespace App\Domain\Contact\Enums;

enum ReplyDeliveryStatus: string
{
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';
}
