<?php

declare(strict_types=1);

namespace App\Users\Resources;

use App\Constants\GeoConstants;
use App\Infrastructure\Services\PsgcService;
use Domain\Users\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserAddress
 */
class UserAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PsgcService $psgc */
        $psgc = app(PsgcService::class);

        $names = $psgc->resolveNames([
            'region_code' => $this->region_code,
            'province_code' => $this->province_code,
            'city_municipality_code' => $this->city_municipality_code,
            'barangay_code' => $this->barangay_code,
        ]);

        $parts = array_filter([
            $this->street,
            $names['barangay'] !== null ? "Brgy. {$names['barangay']}" : null,
            $names['city_municipality'],
            $names['province'],
            $names['region'],
            'Philippines',
        ]);

        $mapsUrl = null;
        if ($this->latitude !== null && $this->longitude !== null) {
            $mapsUrl = GeoConstants::MAPS_SEARCH_URL.urlencode("{$this->latitude},{$this->longitude}");
        } elseif ($parts !== []) {
            $mapsUrl = GeoConstants::MAPS_SEARCH_URL.urlencode(implode(', ', $parts));
        }

        return [
            'id' => $this->id,
            'label' => $this->label,
            'region_code' => $this->region_code,
            'province_code' => $this->province_code,
            'city_municipality_code' => $this->city_municipality_code,
            'barangay_code' => $this->barangay_code,
            'street' => $this->street,
            'region' => $names['region'],
            'province' => $names['province'],
            'city_municipality' => $names['city_municipality'],
            'barangay' => $names['barangay'],
            'formatted_address' => implode(', ', $parts),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'maps_url' => $mapsUrl,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
