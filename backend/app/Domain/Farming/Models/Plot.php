<?php

namespace Domain\Farming\Models;

use Domain\Farming\Enums\SoilType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plot extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'polygon',
        'soil_type',
        'calculated_area',
        'agromonitoring_polyid',
    ];

    protected function casts(): array
    {
        return [
            'soil_type' => SoilType::class,
        ];
    }

    /**
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function recommendations()
    {
        return $this->hasMany(\App\Domain\CropRecommendation\Models\CropRecommendation::class);
    }

    public function getCentroid(): ?array
    {
        try {
            $result = \Illuminate\Support\Facades\DB::selectOne(
                "SELECT ST_Y(ST_Centroid(polygon::geometry)) as lat, ST_X(ST_Centroid(polygon::geometry)) as lon FROM plots WHERE id = ?",
                [$this->id]
            );
            if ($result && isset($result->lat) && isset($result->lon)) {
                return [
                    'lat' => (float) $result->lat,
                    'lon' => (float) $result->lon,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback for non-PostGIS or mock environments
        }

        return null;
    }

    public static function reverseGeocodeCoordinates(float $lat, float $lon): ?array
    {
        $rLat = round($lat, 3);
        $rLon = round($lon, 3);
        $cacheKey = "geo_coord_{$rLat}_{$rLon}";

        $geo = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_array($geo) && (!empty($geo['city']) || !empty($geo['country']))) {
            return $geo;
        }

        $geo = null;

        // Provider A: Photon (OpenStreetMap mirror, fast and structured)
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(4)
                ->get('https://photon.komoot.io/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                ]);

            if ($response->successful()) {
                $props = $response->json('features.0.properties') ?? [];
                if (!empty($props)) {
                    $geo = [
                        'city' => $props['city'] ?? $props['town'] ?? $props['municipality'] ?? $props['locality'] ?? $props['name'] ?? null,
                        'state' => $props['state'] ?? $props['county'] ?? null,
                        'country' => $props['country'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Photon reverse geocoding failed: ' . $e->getMessage());
        }

        // Provider B: BigDataCloud free reverse geocoder fallback
        if (empty($geo['city']) && empty($geo['country'])) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(4)
                    ->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'localityLanguage' => 'en',
                    ]);

                if ($response->successful()) {
                    $data = $response->json() ?? [];
                    $cityCandidate = $data['city'] ?? $data['locality'] ?? null;
                    $stateCandidate = null;

                    if (!empty($data['localityInfo']['administrative'])) {
                        foreach ($data['localityInfo']['administrative'] as $admin) {
                            if (($admin['adminLevel'] ?? 0) === 4 || stripos($admin['description'] ?? '', 'province') !== false) {
                                $stateCandidate = $admin['name'];
                                break;
                            }
                        }
                    }

                    if (empty($stateCandidate) && !empty($data['principalSubdivision'])) {
                        $stateCandidate = preg_replace('/\s*\(.*?\)/', '', $data['principalSubdivision']);
                    }

                    $geo = [
                        'city' => $cityCandidate,
                        'state' => $stateCandidate,
                        'country' => $data['countryName'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('BigDataCloud reverse geocoding failed: ' . $e->getMessage());
            }
        }

        // Cache ONLY when we have successfully resolved real location data
        if (!empty($geo['city']) || !empty($geo['country'])) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $geo, 86400 * 30);
            return $geo;
        }

        return null;
    }

    public function resolveLocation(): array
    {
        $city = null;
        $state = null;
        $country = null;

        // 1. Primary: Reverse-geocode the plot's actual drawn polygon coordinates on the map
        $centroid = $this->getCentroid();
        if ($centroid && !empty($centroid['lat']) && !empty($centroid['lon'])) {
            $geo = self::reverseGeocodeCoordinates((float) $centroid['lat'], (float) $centroid['lon']);
            if (!empty($geo)) {
                $city = $geo['city'] ?? null;
                $state = $geo['state'] ?? null;
                $country = $geo['country'] ?? null;
            }
        }

        // 2. Fallback to the Farm's registered address only if coordinates yielded nothing
        $farm = $this->farm;
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
            'city' => $city ?: 'Local Region',
            'state' => $state ?: '',
            'country' => $country ?: 'Philippines',
        ];
    }
}
