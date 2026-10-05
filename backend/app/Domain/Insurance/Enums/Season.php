<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum Season: string
{
    case WET = 'wet';
    case DRY = 'dry';
}
