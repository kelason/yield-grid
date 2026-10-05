<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\GeoConstants;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reverse-geocode coordinates to city/state/country via third-party providers.
 *
 * Primary: Photon (OpenStreetMap mirror). Fallback: BigDataCloud free tier.
 * Successful resolutions are cached; failures are never cached.
 */
final class ReverseGeocodeService
{
    private string $photonUrl;

    private string $bigDataCloudUrl;

    public function __construct()
    {
        $this->photonUrl = (string) config('services.photon.reverse_url', GeoConstants::PHOTON_REVERSE_GEOCODE_URL);
        $this->bigDataCloudUrl = (string) config('services.bigdatacloud.reverse_url', GeoConstants::BIGDATACLOUD_REVERSE_GEOCODE_URL);
    }

    /**
     * @return array{city: ?string, state: ?string, country: ?string}|null
     */
    public function reverseGeocode(float $lat, float $lon): ?array
    {
        $rLat = round($lat, 3);
        $rLon = round($lon, 3);
        // Key format is historic: existing cache rows use it, keep it stable.
        $cacheKey = "geo_coord_{$rLat}_{$rLon}";

        $geo = $this->readCache($cacheKey);
        if (is_array($geo) && (! empty($geo['city']) || ! empty($geo['country']))) {
            return $geo;
        }

        $geo = $this->viaPhoton($lat, $lon);

        if (empty($geo['city']) && empty($geo['country'])) {
            $geo = $this->viaBigDataCloud($lat, $lon);
        }

        // Cache ONLY when we have successfully resolved real location data
        if (! empty($geo['city']) || ! empty($geo['country'])) {
            $this->writeCache($cacheKey, $geo);

            return $geo;
        }

        return null;
    }

    /**
     * Resolve a plot's location: reverse-geocode its centroid first, then fall
     * back to the farm's registered address, then to country defaults.
     *
     * @return array{city: string, state: string, country: string}
     */
    public function resolvePlotLocation(Plot $plot): array
    {
        $city = null;
        $state = null;
        $country = null;

        // 1. Primary: Reverse-geocode the plot's actual drawn polygon coordinates on the map
        $centroid = $plot->getCentroid();
        if ($centroid && ! empty($centroid['lat']) && ! empty($centroid['lon'])) {
            $geo = $this->reverseGeocode((float) $centroid['lat'], (float) $centroid['lon']);
            if (! empty($geo)) {
                $city = $geo['city'] ?? null;
                $state = $geo['state'] ?? null;
                $country = $geo['country'] ?? null;
            }
        }

        // 2. Fallback to the Farm's registered address only if coordinates yielded nothing
        $farm = $plot->farm;
        if (empty($city) && $farm?->city) {
            $city = $farm->city;
        }
        if (empty($state) && $farm?->state) {
            $state = $farm->state;
        }
        if (empty($country) && $farm?->country) {
            $country = $farm->country;
        }

        return [
            'city' => $city ?: GeoConstants::FALLBACK_LOCATION_CITY,
            'state' => $state ?: '',
            'country' => $country ?: GeoConstants::FALLBACK_LOCATION_COUNTRY,
        ];
    }

    /**
     * @return array{city: ?string, state: ?string, country: ?string}|null
     */
    private function viaPhoton(float $lat, float $lon): ?array
    {
        try {
            $response = Http::timeout(GeoConstants::REVERSE_GEOCODE_HTTP_TIMEOUT_SECONDS)
                ->get($this->photonUrl, [
                    'lat' => $lat,
                    'lon' => $lon,
                ]);

            if ($response->successful()) {
                $props = $response->json('features.0.properties') ?? [];
                if (! empty($props)) {
                    return [
                        'city' => $props['city'] ?? $props['town'] ?? $props['municipality'] ?? $props['locality'] ?? $props['name'] ?? null,
                        'state' => $props['state'] ?? $props['county'] ?? null,
                        'country' => $props['country'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->warn('Photon reverse geocoding failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * @return array{city: ?string, state: ?string, country: ?string}|null
     */
    private function viaBigDataCloud(float $lat, float $lon): ?array
    {
        try {
            $response = Http::timeout(GeoConstants::REVERSE_GEOCODE_HTTP_TIMEOUT_SECONDS)
                ->get($this->bigDataCloudUrl, [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'localityLanguage' => 'en',
                ]);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                $cityCandidate = $data['city'] ?? $data['locality'] ?? null;
                $stateCandidate = null;

                if (! empty($data['localityInfo']['administrative'])) {
                    foreach ($data['localityInfo']['administrative'] as $admin) {
                        if (($admin['adminLevel'] ?? 0) === 4 || stripos($admin['description'] ?? '', 'province') !== false) {
                            $stateCandidate = $admin['name'];
                            break;
                        }
                    }
                }

                if (empty($stateCandidate) && ! empty($data['principalSubdivision'])) {
                    $stateCandidate = preg_replace('/\s*\(.*?\)/', '', $data['principalSubdivision']);
                }

                return [
                    'city' => $cityCandidate,
                    'state' => $stateCandidate,
                    'country' => $data['countryName'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            $this->warn('BigDataCloud reverse geocoding failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Read a cached resolution. A broken cache backend must degrade to a
     * cache miss, never break the request (e.g. unwritable storage).
     */
    private function readCache(string $cacheKey): mixed
    {
        try {
            return Cache::get($cacheKey);
        } catch (\Throwable $e) {
            $this->warn('Reverse-geocode cache read failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param  array{city: ?string, state: ?string, country: ?string}  $geo
     */
    private function writeCache(string $cacheKey, array $geo): void
    {
        try {
            Cache::put($cacheKey, $geo, GeoConstants::REVERSE_GEOCODE_CACHE_TTL_SECONDS);
        } catch (\Throwable $e) {
            $this->warn('Reverse-geocode cache write failed: '.$e->getMessage());
        }
    }

    /**
     * Log without ever throwing: observability must not break geocoding
     * when the log stream itself is unavailable (e.g. bad file ownership).
     */
    private function warn(string $message): void
    {
        try {
            Log::warning($message);
        } catch (\Throwable) {
            // Intentionally swallowed: logging must never break the request.
        }
    }
}
