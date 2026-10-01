<?php

declare(strict_types=1);

namespace App\Users\Requests;

use App\Constants\AddressConstants;
use App\Constants\GeoConstants;
use App\Infrastructure\Services\PsgcService;
use Illuminate\Validation\Validator;

/**
 * Shared PSGC address validation: code hierarchy plus Leaflet-pin radius guard.
 */
final class UserAddressRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(string $prefix = '', bool $optionalParent = false): array
    {
        $key = fn (string $field): string => $prefix === '' ? $field : "{$prefix}.{$field}";
        $required = ($optionalParent && $prefix !== '') ? "required_with:{$prefix}" : 'required';

        return [
            $key('label') => ['nullable', 'string', 'max:'.AddressConstants::LABEL_MAX_LENGTH],
            $key('region_code') => [$required, 'string', 'max:'.GeoConstants::CODE_LENGTH],
            $key('province_code') => ['nullable', 'string', 'max:'.GeoConstants::CODE_LENGTH],
            $key('city_municipality_code') => [$required, 'string', 'max:'.GeoConstants::CODE_LENGTH],
            $key('barangay_code') => [$required, 'string', 'max:'.GeoConstants::CODE_LENGTH],
            $key('street') => ['nullable', 'string', 'max:'.AddressConstants::STREET_MAX_LENGTH],
            $key('latitude') => ['nullable', 'numeric', 'between:'.GeoConstants::LATITUDE_MIN.','.GeoConstants::LATITUDE_MAX, 'required_with:'.$key('longitude')],
            $key('longitude') => ['nullable', 'numeric', 'between:'.GeoConstants::LONGITUDE_MIN.','.GeoConstants::LONGITUDE_MAX, 'required_with:'.$key('latitude')],
            $key('is_default') => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function validateSemantics(Validator $validator, array $data, PsgcService $psgc, string $prefix = ''): void
    {
        $validator->after(function (Validator $validator) use ($data, $psgc, $prefix): void {
            $value = fn (string $field): mixed => $data[$field] ?? null;

            if (! isset($data['region_code'], $data['city_municipality_code'], $data['barangay_code'])) {
                return;
            }

            $provinceCode = $value('province_code') !== null ? (string) $value('province_code') : null;
            if (! $psgc->validateHierarchy(
                (string) $value('region_code'),
                $provinceCode,
                (string) $value('city_municipality_code'),
                (string) $value('barangay_code')
            )) {
                $validator->errors()->add($prefix === '' ? 'barangay_code' : "{$prefix}.barangay_code", 'The selected area is not a valid region, province, city, barangay combination.');

                return;
            }

            if ($value('latitude') === null || $value('longitude') === null) {
                return;
            }

            $names = $psgc->resolveNames([
                'region_code' => (string) $value('region_code'),
                'province_code' => $provinceCode,
                'city_municipality_code' => (string) $value('city_municipality_code'),
                'barangay_code' => (string) $value('barangay_code'),
            ]);

            if ($names['barangay'] === null || $names['city_municipality'] === null) {
                return;
            }

            $center = $psgc->geocodeCenter($names['barangay'], $names['city_municipality'], $names['province']);
            if ($center === null) {
                return;
            }

            $distanceKm = PsgcService::haversineKm(
                (float) $value('latitude'),
                (float) $value('longitude'),
                $center['lat'],
                $center['lng']
            );

            if ($distanceKm > GeoConstants::MAX_PIN_RADIUS_KM) {
                $field = $prefix === '' ? 'latitude' : "{$prefix}.latitude";
                $validator->errors()->add($field, 'The pinned location must be within the selected barangay, city, and province.');
            }
        });
    }
}
