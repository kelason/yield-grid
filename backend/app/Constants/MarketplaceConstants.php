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

    public const DEMAND_TITLE_MAX_LENGTH = 255;

    public const DEMAND_DESCRIPTION_MAX_LENGTH = 2000;

    public const DEMAND_CROP_NAME_MAX_LENGTH = 100;

    public const DEMAND_QUANTITY_MIN_KG = 0.01;

    public const DEMAND_QUANTITY_MAX_KG = 1000000;

    public const DEMAND_PRICE_MIN = 0.01;

    public const DEMAND_PRICE_MAX = 1000000;

    public const CHECKOUT_QUANTITY_MIN_KG = 1;

    public const CHECKOUT_QUANTITY_MAX_KG = 9999;

    public const CASH_APPROVAL_AMOUNT_MIN = 0.01;

    public const CASH_APPROVAL_AMOUNT_MAX = 99999999;

    public const CONTRACT_TITLE_MAX_LENGTH = 255;

    public const CONTRACT_DESCRIPTION_MAX_LENGTH = 5000;

    public const CONTRACT_QUANTITY_MIN_KG = 1;

    public const CONTRACT_QUANTITY_MAX_KG = 99999999;

    public const CONTRACT_PRICE_MIN = 0.01;

    public const CONTRACT_PRICE_MAX = 99999999;

    public const LISTING_TITLE_MAX_LENGTH = 50;

    public const LISTING_DESCRIPTION_MAX_LENGTH = 5000;

    public const LISTING_CROP_NAME_MAX_LENGTH = 100;

    public const LISTING_QUANTITY_MIN_KG = 1;

    public const LISTING_QUANTITY_MAX_KG = 999999;

    public const LISTING_PRICE_MIN = 0.01;

    public const LISTING_PRICE_MAX = 99999999;

    public const LISTING_SHELF_LIFE_MIN_DAYS = 1;

    public const LISTING_SHELF_LIFE_MAX_DAYS = 9999;

    public const PRICE_GUIDE_CROP_MAX_LENGTH = 100;

    public const PRICE_GUIDE_REGION_MAX_LENGTH = 20;

    public const PRICE_GUIDE_BATCH_MAX_CROPS = 50;

    public const PRICE_GUIDE_CACHE_TTL_SECONDS = 3600;

    public const PRICE_GUIDE_CATALOG_CACHE_TTL_SECONDS = 3600;

    public const PRICE_GUIDE_STALE_AFTER_DAYS = 3;

    public const PRICE_GUIDE_FUZZY_MAX_DAMERAU_DISTANCE = 1;

    public const PRICE_GUIDE_FUZZY_MIN_LENGTH = 4;

    public const PRICE_GUIDE_AI_CONFIDENCE_MIN = 70;

    public const PRICE_GUIDE_AI_LEARN_MIN_LENGTH = 3;

    public const PRICE_GUIDE_SYNC_TIMEOUT_SECONDS = 30;

    public const PRICE_GUIDE_SYNC_RATE_LIMIT_PER_MINUTE = 60;

    public const PRICE_GUIDE_MARKETPLACE_AVG_DAYS = 30;

    public const PRICE_GUIDE_MARKETPLACE_AVG_MAX_ROWS = 500;

    public const PRICE_GUIDE_AI_EXTRACT_MAX_CHARS = 12000;

    public const PRICE_GUIDE_AI_ESTIMATE_MAX_PRICE = 1000000;

    public const PRICE_GUIDE_SYNC_REQUEST_TIMEOUT_SECONDS = 10;

    public const PRICE_GUIDE_SYNC_MAX_ATTEMPTS = 10;
}
