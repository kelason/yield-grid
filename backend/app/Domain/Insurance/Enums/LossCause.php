<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum LossCause: string
{
    case TYPHOON = 'typhoon';
    case FLOOD = 'flood';
    case DROUGHT = 'drought';
    case PEST = 'pest';
    case DISEASE = 'disease';
    case OTHER = 'other';
}
