<?php

declare(strict_types=1);

namespace App\Users\Controllers;

use App\Constants\GeoConstants;
use App\Http\Controllers\Controller;
use App\Infrastructure\Services\PsgcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GeoController extends Controller
{
    public function __construct(
        private readonly PsgcService $psgc,
    ) {}

    public function regions(): JsonResponse
    {
        return response()->json(['data' => $this->psgc->regions()]);
    }

    public function provinces(Request $request): JsonResponse
    {
        $validated = $request->validate(['region_code' => ['nullable', 'string', 'max:'.GeoConstants::CODE_LENGTH]]);

        return response()->json(['data' => $this->psgc->provinces($validated['region_code'] ?? null)]);
    }

    public function citiesMunicipalities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_code' => ['nullable', 'string', 'max:'.GeoConstants::CODE_LENGTH],
            'region_code' => ['nullable', 'string', 'max:'.GeoConstants::CODE_LENGTH],
        ]);

        return response()->json(['data' => $this->psgc->citiesMunicipalities(
            $validated['province_code'] ?? null,
            $validated['region_code'] ?? null
        )]);
    }

    public function barangays(Request $request): JsonResponse
    {
        $validated = $request->validate(['city_municipality_code' => ['required', 'string', 'max:'.GeoConstants::CODE_LENGTH]]);

        return response()->json(['data' => $this->psgc->barangays($validated['city_municipality_code'])]);
    }

    /**
     * Map center for the Leaflet pin picker, given resolved area names.
     */
    public function center(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barangay' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ]);

        $center = $this->psgc->geocodeCenter(
            $validated['barangay'],
            $validated['city_municipality'],
            $validated['province'] ?? null
        ) ?? ['lat' => GeoConstants::FALLBACK_CENTER_LAT, 'lng' => GeoConstants::FALLBACK_CENTER_LNG];

        return response()->json([
            'data' => [
                'lat' => $center['lat'],
                'lng' => $center['lng'],
                'max_radius_km' => GeoConstants::MAX_PIN_RADIUS_KM,
            ],
        ]);
    }
}
