<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ContentReportReason: string
{
    case SPAM = 'spam';
    case INAPPROPRIATE = 'inappropriate';
    case MISINFORMATION = 'misinformation';
    case HARASSMENT = 'harassment';
    case OFF_TOPIC = 'off_topic';
    case OTHER = 'other';
    case SUSPECTED_FRAUD = 'suspected_fraud';
    case PROHIBITED_ITEM = 'prohibited_item';
}
