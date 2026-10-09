<?php

declare(strict_types=1);

namespace Domain\Farming\Enums;

enum VerificationMethod: string
{
    case FIELD_VISIT = 'field_visit';
    case PHONE_CHECK = 'phone_check';
    case DOCUMENT_CHECK = 'document_check';
    case OTHER = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
