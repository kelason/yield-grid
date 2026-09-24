<?php

declare(strict_types=1);

namespace App\Domain\Community\Enums;

enum ReportReason: string
{
    case SPAM = 'spam';
    case INAPPROPRIATE = 'inappropriate';
    case MISINFORMATION = 'misinformation';
    case HARASSMENT = 'harassment';
    case OFF_TOPIC = 'off_topic';
    case OTHER = 'other';
}
