<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Enums;

enum PackStatus: string
{
    case NONE = 'none';
    case GENERATING = 'generating';
    case READY = 'ready';
    case FAILED = 'failed';
}
