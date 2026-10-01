<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum PriceSource: string
{
    case DA_BANTAY_PRESYO = 'da_bantay_presyo';
    case MARKETPLACE_AVERAGE = 'marketplace_average';
    case MANUAL = 'manual';
    case AI_ESTIMATE = 'ai_estimate';
}
