<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum CashPaymentStatus: string
{
    case PENDING_APPROVAL = 'pending_approval';
    case PARTIALLY_PAID = 'partially_paid';
    case FULLY_PAID = 'fully_paid';
    case REJECTED = 'rejected';
}
