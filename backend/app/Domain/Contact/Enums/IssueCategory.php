<?php

declare(strict_types=1);

namespace App\Domain\Contact\Enums;

enum IssueCategory: string
{
    case TECHNICAL = 'technical';
    case ACCOUNT = 'account';
    case MARKETPLACE = 'marketplace';
    case PAYMENT = 'payment';
    case OTHER = 'other';
}
