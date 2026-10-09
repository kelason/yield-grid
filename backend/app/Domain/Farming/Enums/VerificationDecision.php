<?php

declare(strict_types=1);

namespace Domain\Farming\Enums;

enum VerificationDecision: string
{
    case VERIFY = 'verify';
    case REJECT = 'reject';
    case REVOKE = 'revoke';
    case REOPEN = 'reopen';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
