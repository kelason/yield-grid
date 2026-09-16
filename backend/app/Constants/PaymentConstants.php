<?php

declare(strict_types=1);

namespace App\Constants;

final class PaymentConstants
{
    public const DEFAULT_CURRENCY = 'PHP';

    public const CENTAVO_MULTIPLIER = 100;

    public const CHECKOUT_EXPIRY_MINUTES = 30;

    public const WEBHOOK_TOLERANCE_SECONDS = 300;

    public const EVENT_PAYMENT_PAID = 'checkout_session.payment.paid';

    public const EVENT_PAYMENT_FAILED = 'checkout_session.payment.failed';

    public const EVENT_SESSION_EXPIRED = 'checkout_session.expired';
}
