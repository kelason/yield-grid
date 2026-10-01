<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum PriceTier: string
{
    case FARMGATE = 'farmgate';
    case WHOLESALE = 'wholesale';
    case RETAIL = 'retail';
}
