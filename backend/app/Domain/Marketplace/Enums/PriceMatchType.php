<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum PriceMatchType: string
{
    case EXACT = 'exact';
    case ALIAS = 'alias';
    case FUZZY = 'fuzzy';
    case AI_CORRECTED = 'ai_corrected';
}
