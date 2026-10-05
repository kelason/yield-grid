<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum RsbsaStatus: string
{
    case NOT_REGISTERED = 'not_registered';
    case REGISTERED = 'registered';
}
