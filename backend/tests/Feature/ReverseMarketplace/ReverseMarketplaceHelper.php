<?php

declare(strict_types=1);

namespace Tests\Feature\ReverseMarketplace;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class ReverseMarketplaceHelper
{
    public const REGION_NCR = '130000000';

    public const CITY_MAKATI = '133900000';

    public const BARANGAY_POBLACION = '1339010018';

    public const REGION_CALABARZON = '040000000';

    public const PROVINCE_CAVITE = '042100000';

    public const CITY_DASMARIAS = '0421000001';

    public const BARANGAY_ZONE = '042100000101';

    public static function fakePsgc(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/regions.json' => Http::response([
                ['code' => self::REGION_NCR, 'name' => 'NCR', 'regionName' => 'National Capital Region'],
                ['code' => self::REGION_CALABARZON, 'name' => 'CALABARZON', 'regionName' => 'Region IV-A'],
            ]),
            'psgc.gitlab.io/api/provinces.json' => Http::response([
                ['code' => self::PROVINCE_CAVITE, 'name' => 'Cavite', 'regionCode' => self::REGION_CALABARZON],
            ]),
            'psgc.gitlab.io/api/regions/'.self::REGION_NCR.'/provinces/' => Http::response([]),
            'psgc.gitlab.io/api/regions/'.self::REGION_CALABARZON.'/provinces/' => Http::response([
                ['code' => self::PROVINCE_CAVITE, 'name' => 'Cavite', 'regionCode' => self::REGION_CALABARZON],
            ]),
            'psgc.gitlab.io/api/regions/'.self::REGION_NCR.'/cities-municipalities/' => Http::response([
                ['code' => self::CITY_MAKATI, 'name' => 'Makati', 'regionCode' => self::REGION_NCR],
            ]),
            'psgc.gitlab.io/api/provinces/'.self::PROVINCE_CAVITE.'/cities-municipalities/' => Http::response([
                ['code' => self::CITY_DASMARIAS, 'name' => 'Dasmarinas', 'provinceCode' => self::PROVINCE_CAVITE],
            ]),
            'psgc.gitlab.io/api/cities-municipalities/'.self::CITY_MAKATI.'/barangays/' => Http::response([
                ['code' => self::BARANGAY_POBLACION, 'name' => 'Poblacion', 'cityCode' => self::CITY_MAKATI],
            ]),
            'psgc.gitlab.io/api/cities-municipalities/'.self::CITY_DASMARIAS.'/barangays/' => Http::response([
                ['code' => self::BARANGAY_ZONE, 'name' => 'Zone I', 'cityCode' => self::CITY_DASMARIAS],
            ]),
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '14.5500000', 'lon' => '121.0300000'],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Home',
            'street' => '123 Sampaguita St.',
            'region_code' => self::REGION_NCR,
            'province_code' => null,
            'city_municipality_code' => self::CITY_MAKATI,
            'barangay_code' => self::BARANGAY_POBLACION,
            'latitude' => 14.551,
            'longitude' => 121.031,
            'is_default' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function makeAddress(User $user, array $overrides = []): UserAddress
    {
        $address = UserAddress::create(array_merge([
            'user_id' => $user->id,
            'label' => 'Home',
            'region_code' => self::REGION_NCR,
            'province_code' => null,
            'city_municipality_code' => self::CITY_MAKATI,
            'barangay_code' => self::BARANGAY_POBLACION,
            'street' => '123 Sampaguita St.',
            'latitude' => 14.551,
            'longitude' => 121.031,
            'is_default' => true,
        ], $overrides));

        if ($address->latitude !== null && $address->longitude !== null) {
            DB::update(
                'UPDATE user_addresses SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
                [(float) $address->longitude, (float) $address->latitude, $address->id]
            );
        }

        return $address->fresh() ?? $address;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function makeDemand(User $buyer, ?UserAddress $address = null, array $overrides = []): CropDemand
    {
        $address ??= self::makeAddress($buyer);

        return CropDemand::create(array_merge([
            'buyer_id' => $buyer->id,
            'address_id' => $address->id,
            'title' => '600kg fresh tomatoes',
            'description' => 'For weekend market',
            'crop_name' => 'Tomato',
            'quantity_kg' => 600,
            'remaining_quantity_kg' => 600,
            'target_price_per_kg' => 45,
            'total_budget' => 27000,
            'currency' => 'PHP',
            'needed_by_date' => now()->addDays(30)->toDateString(),
            'expiry_date' => now()->addDays(15)->toDateString(),
            'status' => DemandStatus::OPEN,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function makeOffer(CropDemand $demand, User $farmer, array $overrides = []): CropDemandOffer
    {
        $quantity = (float) ($overrides['quantity_kg'] ?? 150);
        $price = (float) ($overrides['price_per_kg'] ?? 44);

        return CropDemandOffer::create(array_merge([
            'crop_demand_id' => $demand->id,
            'farmer_id' => $farmer->id,
            'quantity_kg' => $quantity,
            'price_per_kg' => $price,
            'total_price' => $quantity * $price,
            'currency' => 'PHP',
            'message' => 'Fresh harvest available',
            'status' => DemandOfferStatus::PENDING,
        ], $overrides));
    }
}
