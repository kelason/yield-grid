<?php

declare(strict_types=1);

namespace App\Constants;

final class MarketplaceConstants
{
    public const OFFER_QUANTITY_MIN_KG = 0.01;

    public const OFFER_QUANTITY_MAX_KG = 1000000;

    public const OFFER_PRICE_MIN = 0.01;

    public const OFFER_PRICE_MAX = 99999999;

    public const OFFER_MESSAGE_MAX_LENGTH = 1000;

    public const ORDER_TOTAL_MAX = 9999999999.99;
}
