<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum InsuranceProgram: string
{
    case RICE = 'rice';
    case CORN = 'corn';
}
