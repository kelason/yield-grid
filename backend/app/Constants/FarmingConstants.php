<?php

declare(strict_types=1);

namespace App\Constants;

final class FarmingConstants
{
    public const int FARM_NAME_MAX_LENGTH = 255;

    public const int FARM_ADDRESS_MAX_LENGTH = 255;

    public const int FARM_CITY_MAX_LENGTH = 255;

    public const int FARM_STATE_MAX_LENGTH = 255;

    public const int FARM_COUNTRY_MAX_LENGTH = 255;

    public const int FARM_ZIP_MAX_LENGTH = 50;

    public const int TOTAL_AREA_MIN_HECTARES = 0;

    /**
     * Generous upper bound for farm area (float column has no DB cap).
     * Covers any real-world farm while rejecting garbage input.
     */
    public const int TOTAL_AREA_MAX_HECTARES = 1000000;

    public const int PLOT_NAME_MAX_LENGTH = 255;

    /** Minimum vertices of a plot polygon ring (triangle). */
    public const int POLYGON_MIN_POINTS = 3;

    /** Coordinate pair size: [lng, lat]. */
    public const int COORD_PAIR_SIZE = 2;

    public const int VERIFICATION_NOTE_MAX_LENGTH = 1000;
}
