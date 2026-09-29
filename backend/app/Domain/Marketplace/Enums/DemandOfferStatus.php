<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum DemandOfferStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    /**
     * @return list<string>
     */
    public static function chatEligible(): array
    {
        return [self::ACCEPTED->value, self::PARTIALLY_PAID->value, self::PAID->value, self::DELIVERED->value, self::COMPLETED->value];
    }

    /**
     * @return list<string>
     */
    public static function blockingNewOffer(): array
    {
        return [self::PENDING->value, self::ACCEPTED->value, self::PARTIALLY_PAID->value, self::PAID->value, self::DELIVERED->value];
    }
}
