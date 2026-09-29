<?php

declare(strict_types=1);

namespace Domain\Users\DTOs;

readonly class UpsertUserAddressDTO
{
    public function __construct(
        public int $userId,
        public string $regionCode,
        public ?string $provinceCode,
        public string $cityMunicipalityCode,
        public string $barangayCode,
        public ?string $street = null,
        public ?string $label = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isDefault = false,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $userId, array $validated): self
    {
        return new self(
            userId: $userId,
            regionCode: (string) $validated['region_code'],
            provinceCode: isset($validated['province_code']) ? (string) $validated['province_code'] : null,
            cityMunicipalityCode: (string) $validated['city_municipality_code'],
            barangayCode: (string) $validated['barangay_code'],
            street: isset($validated['street']) ? (string) $validated['street'] : null,
            label: isset($validated['label']) ? (string) $validated['label'] : null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            isDefault: (bool) ($validated['is_default'] ?? false),
        );
    }
}
