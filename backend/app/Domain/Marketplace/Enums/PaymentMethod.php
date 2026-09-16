<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Enums;

enum PaymentMethod: string
{
    case GCASH = 'gcash';
    case MAYA = 'paymaya';
    case CARD = 'card';
    case QR_PH = 'qrph';
    case GRAB_PAY = 'grab_pay';
}
