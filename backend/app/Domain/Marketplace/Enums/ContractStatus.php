<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum ContractStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case PARTIALLY_PAID = 'partially_paid';
    case SOLD = 'sold';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
