<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Services;

use App\Domain\CropRecommendation\Models\WeatherCache;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AgroMonitoringService
{
    private const MOCK_WEATHER_TEMP_KELVIN = 297.15;

    private const MOCK_WEATHER_HUMIDITY = 60;

    private const MOCK_SOIL_MOISTURE = 0.28;

    private const MOCK_SOIL_SURFACE_TEMP = 296.15;

    private const MOCK_SOIL_10CM_TEMP = 294.15;

    private string $apiKey;

    private string $baseUrl = 'https://api.agromonitoring.com/agro/1.0';

    private int $cacheTtlSeconds = 3600; // Cache weather and soil for 1 hour

    private int $rateLimitRpm = 30;      // Maximum 30 requests per minute

    public function __construct()
    {
        $this->apiKey = (string) (config('services.agromonitoring.key') ?? '');
    }

    public function getPlotData(Plot $plot): array
    {
        // 1. Check in-memory / redis cache first (1-hour cache per plot)
        $cacheKey = "agromonitoring_plot_data_{$plot->id}";
        $cachedData = Cache::get($cacheKey);
        if (is_array($cachedData) && ! empty($cachedData['weather']) && ! empty($cachedData['soil'])) {
            Log::info("AgroMonitoring: Serving cached weather & soil data for plot {$plot->id}.");

            return $cachedData;
        }

        if (empty($this->apiKey)) {
            Log::warning('AgroMonitoring API key is missing. Using mock data.');

            return $this->getFallbackOrMockData($plot);
        }

        // 2. Rate Limiting Check (max 30 requests per minute)
        $rateKey = 'agromonitoring_api_rate_limit';
        if (RateLimiter::tooManyAttempts($rateKey, $this->rateLimitRpm)) {
            $seconds = RateLimiter::availableIn($rateKey);
            Log::warning("AgroMonitoring API rate limit reached. Backing off for {$seconds}s. Serving fallback.");

            return $this->getFallbackOrMockData($plot);
        }

        $polyId = $plot->agromonitoring_polyid;

        if (! $polyId) {
            RateLimiter::hit($rateKey, 60);
            $polyId = $this->registerPolygon($plot);
            if ($polyId) {
                $plot->update(['agromonitoring_polyid' => $polyId]);
            } else {
                return $this->getFallbackOrMockData($plot);
            }
        }

        // Hit rate limiter for weather & soil fetch
        RateLimiter::hit($rateKey, 60);
        $weather = $this->getCurrentWeather($polyId);
        $soil = $this->getSoilData($polyId);

        if (! $weather || ! $soil) {
            return $this->getFallbackOrMockData($plot);
        }

        $plotData = [
            'weather' => $weather,
            'soil' => $soil,
        ];

        // Cache the live result for 1 hour
        Cache::put($cacheKey, $plotData, $this->cacheTtlSeconds);

        // Persist to WeatherCache table as resilient database fallback
        $this->saveToWeatherCache($plot, $plotData);

        return $plotData;
    }

    private function getFallbackOrMockData(Plot $plot): array
    {
        // Try database WeatherCache table first
        try {
            $centroid = $plot->getCentroid();
            if ($centroid && ! empty($centroid['lat']) && ! empty($centroid['lon'])) {
                $cached = WeatherCache::where('latitude', round((float) $centroid['lat'], 4))
                    ->where('longitude', round((float) $centroid['lon'], 4))
                    ->where('expires_at', '>', now())
                    ->latest()
                    ->first();

                if ($cached && is_array($cached->weather_data)) {
                    Log::info("AgroMonitoring: Using database WeatherCache fallback for plot {$plot->id}.");

                    return $cached->weather_data;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed reading WeatherCache fallback: '.$e->getMessage());
        }

        return $this->getMockData();
    }

    private function saveToWeatherCache(Plot $plot, array $plotData): void
    {
        try {
            $centroid = $plot->getCentroid();
            if ($centroid && ! empty($centroid['lat']) && ! empty($centroid['lon'])) {
                WeatherCache::updateOrCreate(
                    [
                        'latitude' => round((float) $centroid['lat'], 4),
                        'longitude' => round((float) $centroid['lon'], 4),
                    ],
                    [
                        'weather_data' => $plotData,
                        'expires_at' => now()->addHours(2),
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Failed writing to WeatherCache: '.$e->getMessage());
        }
    }

    private function registerPolygon(Plot $plot): ?string
    {
        try {
            $geojsonResult = \Illuminate\Support\Facades\DB::selectOne(
                'SELECT ST_AsGeoJSON(polygon::geometry) as geojson FROM plots WHERE id = ?', 
                [$plot->id]
            );
            $geometry = json_decode($geojsonResult->geojson, true);

            $response = Http::post("{$this->baseUrl}/polygons?appid={$this->apiKey}", [
                'name' => $plot->name,
                'geo_json' => [
                    'type' => 'Feature',
                    'properties' => (object) [],
                    'geometry' => $geometry,
                ],
            ]);

            if ($response->successful()) {
                return $response->json('id');
            }
            Log::warning('AgroMonitoring create polygon failed (falling back to mock data)', ['body' => $response->body()]);
        } catch (\Exception $e) {
            Log::warning('AgroMonitoring Exception (falling back to mock data)', ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function getCurrentWeather(string $polyId): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/weather", [
                'polyid' => $polyId,
                'appid' => $this->apiKey,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('AgroMonitoring weather failed', ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function getSoilData(string $polyId): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/soil", [
                'polyid' => $polyId,
                'appid' => $this->apiKey,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('AgroMonitoring soil failed', ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function getMockData(): array
    {
        return [
            'weather' => [
                'main' => [
                    'temp' => self::MOCK_WEATHER_TEMP_KELVIN, // Kelvin
                    'humidity' => self::MOCK_WEATHER_HUMIDITY,
                ],
                'weather' => [
                    ['description' => 'clear sky', 'main' => 'Clear'],
                ],
            ],
            'soil' => [
                'moisture' => self::MOCK_SOIL_MOISTURE, // m3/m3
                't0' => self::MOCK_SOIL_SURFACE_TEMP, // Surface temp Kelvin
                't10' => self::MOCK_SOIL_10CM_TEMP, // 10cm temp Kelvin
            ],
        ];
    }
}
