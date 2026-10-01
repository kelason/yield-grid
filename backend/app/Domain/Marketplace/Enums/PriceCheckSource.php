<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum PriceCheckSource: string
{
    case DA = 'da';
    case YIELDGRID = 'yieldgrid';
    case AI = 'ai';

    /**
     * First available source in this order wins the guide and leads the
     * sequential UI. Flip this single list to change precedence everywhere.
     *
     * @return list<self>
     */
    public static function precedence(): array
    {
        return [self::YIELDGRID, self::DA, self::AI];
    }
}
