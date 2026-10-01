<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum CashPaymentType: string
{
    case PARTIAL = 'partial';
    case FULL = 'full';
}
