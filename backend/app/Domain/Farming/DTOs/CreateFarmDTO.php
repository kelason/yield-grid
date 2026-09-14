<?php

namespace Domain\Farming\DTOs;

readonly class CreateFarmDTO
{
    public function __construct(
        public int $userId,
        public string $name,
        public ?string $address,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $zip,
        public ?float $totalArea,
    ) {}

    public static function fromRequest(array $validated, int $userId): self
    {
        return new self(
            userId: $userId,
            name: $validated['name'],
            address: $validated['address'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            country: $validated['country'] ?? null,
            zip: $validated['zip'] ?? null,
            totalArea: isset($validated['total_area']) ? (float)$validated['total_area'] : null,
        );
    }
}
