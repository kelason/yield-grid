<?php

declare(strict_types=1);

namespace App\Constants;

final class GeoConstants
{
    public const PSGC_BASE_URL = 'https://psgc.gitlab.io/api';

    public const PSGC_CACHE_TTL_SECONDS = 86400;

    public const PSGC_HTTP_TIMEOUT_SECONDS = 10;

    public const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org';

    public const NOMINATIM_HTTP_TIMEOUT_SECONDS = 5;

    /**
     * Half-width in degrees of the viewbox used to anchor barangay searches
     * to their city. Prevents same-named barangays in other provinces
     * (e.g. another "Bantug") from winning the geocode match.
     */
    public const GEOCODE_VIEWBOX_DEGREES = 0.25;

    /**
     * Maximum distance (km) between the Leaflet pin and the geocoded center
     * of the selected barangay/city/province. The structured PSGC selection
     * remains the authoritative address; the pin adds delivery precision.
     */
    public const MAX_PIN_RADIUS_KM = 15.0;

    /** Fallback map center (Philippines) when geocoding is unavailable. */
    public const FALLBACK_CENTER_LAT = 12.8797;

    public const FALLBACK_CENTER_LNG = 121.7740;

    public const MAPS_SEARCH_URL = 'https://www.google.com/maps/search/?api=1&query=';

    public const METERS_PER_KM = 1000;

    public const CODE_LENGTH = 12;

    public const int PLACE_NAME_MAX_LENGTH = 255;

    public const int LATITUDE_MIN = -90;

    public const int LATITUDE_MAX = 90;

    public const int LONGITUDE_MIN = -180;

    public const int LONGITUDE_MAX = 180;
}
