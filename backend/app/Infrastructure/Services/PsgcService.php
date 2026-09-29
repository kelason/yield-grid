<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Constants\GeoConstants;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proxy + cache for the Philippine Standard Geographic Code (PSGC) API.
 *
 * All responses are normalized to lists of ['code' => string, 'name' => string].
 */
final class PsgcService
{
    private const EARTH_RADIUS_KM = 6371.0;

    private string $baseUrl;

    private string $nominatimUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.psgc.base_url', GeoConstants::PSGC_BASE_URL), '/');
        $this->nominatimUrl = rtrim((string) config('services.nominatim.base_url', GeoConstants::NOMINATIM_BASE_URL), '/');
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function regions(): array
    {
        /** @var list<array{code: string, name: string}> */
        return Cache::remember('psgc.regions', GeoConstants::PSGC_CACHE_TTL_SECONDS, function (): array {
            return $this->normalize($this->fetch('regions.json'));
        });
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function provinces(?string $regionCode = null): array
    {
        $key = 'psgc.provinces.'.($regionCode ?? 'all');

        /** @var list<array{code: string, name: string}> */
        return Cache::remember($key, GeoConstants::PSGC_CACHE_TTL_SECONDS, function () use ($regionCode): array {
            if ($regionCode !== null) {
                $hierarchical = $this->normalize($this->fetch("regions/{$regionCode}/provinces/"));
                if ($hierarchical !== []) {
                    return $hierarchical;
                }
            }

            return $this->filterFlat('provinces.json', ['regionCode' => $regionCode]);
        });
    }

    /**
     * Cities/municipalities of a province, or directly of a region (NCR has no provinces).
     *
     * @return list<array{code: string, name: string}>
     */
    public function citiesMunicipalities(?string $provinceCode = null, ?string $regionCode = null): array
    {
        $key = 'psgc.cities.'.($provinceCode ?? 'none').'.'.($regionCode ?? 'none');

        /** @var list<array{code: string, name: string}> */
        return Cache::remember($key, GeoConstants::PSGC_CACHE_TTL_SECONDS, function () use ($provinceCode, $regionCode): array {
            if ($provinceCode !== null) {
                $hierarchical = $this->normalize($this->fetch("provinces/{$provinceCode}/cities-municipalities/"));
                if ($hierarchical !== []) {
                    return $hierarchical;
                }
            }

            if ($regionCode !== null) {
                $hierarchical = $this->normalize($this->fetch("regions/{$regionCode}/cities-municipalities/"));
                if ($hierarchical !== []) {
                    return $hierarchical;
                }
            }

            if ($provinceCode !== null) {
                return $this->filterFlat('cities-municipalities.json', ['provinceCode' => $provinceCode]);
            }

            return $this->filterFlat('cities-municipalities.json', ['regionCode' => $regionCode]);
        });
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function barangays(string $cityCode): array
    {
        /** @var list<array{code: string, name: string}> */
        return Cache::remember("psgc.barangays.{$cityCode}", GeoConstants::PSGC_CACHE_TTL_SECONDS, function () use ($cityCode): array {
            $hierarchical = $this->normalize($this->fetch("cities-municipalities/{$cityCode}/barangays/"));
            if ($hierarchical !== []) {
                return $hierarchical;
            }

            return $this->filterFlat('barangays.json', [
                'cityCode' => $cityCode,
                'cityMunicipalityCode' => $cityCode,
            ]);
        });
    }

    /**
     * Validate that the codes form a real region → province? → city → barangay chain.
     * A null province is only valid when the city sits directly under the region (NCR).
     */
    public function validateHierarchy(string $regionCode, ?string $provinceCode, string $cityCode, string $barangayCode): bool
    {
        if ($this->findByCode($this->regions(), $regionCode) === null) {
            return false;
        }

        if ($provinceCode !== null) {
            $province = $this->findRawByCode($this->fetch('provinces.json'), $provinceCode);
            if ($province === null || ($province['regionCode'] ?? null) !== $regionCode) {
                return false;
            }
            $cities = $this->citiesMunicipalities($provinceCode, null);
        } else {
            $cities = $this->citiesMunicipalities(null, $regionCode);
        }

        if ($this->findByCode($cities, $cityCode) === null) {
            return false;
        }

        return $this->findByCode($this->barangays($cityCode), $barangayCode) !== null;
    }

    /**
     * Resolve PSGC codes to display names. Unknown codes resolve to null.
     *
     * @param  array{region_code: string, province_code?: ?string, city_municipality_code: string, barangay_code: string}  $codes
     * @return array{region: ?string, province: ?string, city_municipality: ?string, barangay: ?string}
     */
    public function resolveNames(array $codes): array
    {
        $provinceCode = $codes['province_code'] ?? null;

        $provinceName = null;
        if ($provinceCode !== null) {
            $province = $this->findByCode($this->provinces($codes['region_code']), $provinceCode)
                ?? $this->findByCode($this->provinces(null), $provinceCode);
            $provinceName = $province['name'] ?? null;
        }

        $city = $this->findByCode(
            $this->citiesMunicipalities($provinceCode, $codes['region_code']),
            $codes['city_municipality_code']
        );
        $barangay = $this->findByCode(
            $this->barangays($codes['city_municipality_code']),
            $codes['barangay_code']
        );

        return [
            'region' => $this->findByCode($this->regions(), $codes['region_code'])['name'] ?? null,
            'province' => $provinceName,
            'city_municipality' => $city['name'] ?? null,
            'barangay' => $barangay['name'] ?? null,
        ];
    }

    /**
     * Best-effort map center for the selected area, used to position the Leaflet pin picker.
     *
     * Anchored two-step lookup: the city is geocoded first with a structured
     * query (reliable), then the barangay is searched inside a viewbox around
     * that city center so same-named barangays in other provinces cannot win.
     * Falls back to the city center when the barangay is not mapped.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeCenter(string $barangay, string $city, ?string $province = null): ?array
    {
        $cacheKey = 'geo.center.'.md5(implode('|', [$barangay, $city, $province ?? '']));

        /** @var array{lat: float, lng: float}|null */
        return Cache::remember(
            $cacheKey,
            GeoConstants::PSGC_CACHE_TTL_SECONDS,
            function () use ($barangay, $city, $province): ?array {
                $anchor = $this->searchCity($city, $province);

                if ($anchor === null) {
                    return $this->nominatimSearch([
                        'q' => implode(', ', array_filter([$barangay, $city, $province, 'Philippines'])),
                    ]);
                }

                $halfBox = GeoConstants::GEOCODE_VIEWBOX_DEGREES;
                $barangayMatch = $this->nominatimSearch([
                    'q' => implode(', ', array_filter([$barangay, $city, $province, 'Philippines'])),
                    'viewbox' => implode(',', [
                        $anchor['lng'] - $halfBox,
                        $anchor['lat'] + $halfBox,
                        $anchor['lng'] + $halfBox,
                        $anchor['lat'] - $halfBox,
                    ]),
                    'bounded' => 1,
                ]);

                return $barangayMatch ?? $anchor;
            }
        );
    }

    /**
     * Geocode a city/municipality with a structured query. PSGC city names
     * carry a " City" suffix ("Cabanatuan City") that Nominatim's structured
     * search does not match, so retry with the suffix stripped when the
     * verbatim name finds nothing.
     */
    private function searchCity(string $city, ?string $province): ?array
    {
        $anchor = $this->nominatimSearch(array_filter([
            'city' => $city,
            'state' => $province,
            'country' => 'Philippines',
        ]));

        if ($anchor !== null) {
            return $anchor;
        }

        $normalized = $this->normalizeCityForSearch($city);
        if ($normalized === $city) {
            return null;
        }

        return $this->nominatimSearch(array_filter([
            'city' => $normalized,
            'state' => $province,
            'country' => 'Philippines',
        ]));
    }

    private function normalizeCityForSearch(string $city): string
    {
        $stripped = (string) preg_replace('/\s+City$/i', '', $city);

        return (string) preg_replace('/^City of\s+/i', '', $stripped);
    }

    /**
     * Run one Nominatim search and return the first result's coordinates.
     *
     * @param  array<string, mixed>  $params
     * @return array{lat: float, lng: float}|null
     */
    private function nominatimSearch(array $params): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'YieldGrid/1.0'])
                ->timeout(GeoConstants::NOMINATIM_HTTP_TIMEOUT_SECONDS)
                ->get("{$this->nominatimUrl}/search", array_merge([
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'ph',
                ], $params));

            if ($response->failed()) {
                return null;
            }

            /** @var list<array{lat?: string, lon?: string}> $results */
            $results = $response->json() ?? [];
            $first = $results[0] ?? null;

            if ($first === null || ! isset($first['lat'], $first['lon'])) {
                return null;
            }

            return ['lat' => (float) $first['lat'], 'lng' => (float) $first['lon']];
        } catch (\Throwable $e) {
            Log::warning('Nominatim geocoding failed', ['params' => $params, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public static function haversineKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetch(string $path): array
    {
        try {
            $response = Http::timeout(GeoConstants::PSGC_HTTP_TIMEOUT_SECONDS)->get("{$this->baseUrl}/{$path}");

            if ($response->failed()) {
                Log::warning('PSGC API request failed', ['path' => $path, 'status' => $response->status()]);

                return [];
            }

            /** @var mixed $data */
            $data = $response->json();

            return is_array($data) ? array_values($data) : [];
        } catch (\Throwable $e) {
            Log::warning('PSGC API request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{code: string, name: string}>
     */
    private function normalize(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['code'], $row['name'])) {
                continue;
            }
            $normalized[] = ['code' => (string) $row['code'], 'name' => (string) $row['name']];
        }

        return $normalized;
    }

    /**
     * Filter a flat PSGC file by matching ANY of the given parent-key candidates.
     *
     * @param  array<string, ?string>  $parentKeys
     * @return list<array{code: string, name: string}>
     */
    private function filterFlat(string $path, array $parentKeys): array
    {
        $wanted = array_filter($parentKeys, fn (?string $code): bool => $code !== null);
        if ($wanted === []) {
            return $this->normalize($this->fetch($path));
        }

        $matched = [];
        foreach ($this->fetch($path) as $row) {
            if (! is_array($row) || ! isset($row['code'], $row['name'])) {
                continue;
            }
            foreach ($wanted as $key => $code) {
                if (($row[$key] ?? null) === $code) {
                    $matched[] = ['code' => (string) $row['code'], 'name' => (string) $row['name']];
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * @param  list<array{code: string, name: string}>  $rows
     * @return array{code: string, name: string}|null
     */
    private function findByCode(array $rows, string $code): ?array
    {
        foreach ($rows as $row) {
            if ($row['code'] === $code) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    private function findRawByCode(array $rows, string $code): ?array
    {
        foreach ($rows as $row) {
            if (is_array($row) && ($row['code'] ?? null) === $code) {
                return $row;
            }
        }

        return null;
    }
}
