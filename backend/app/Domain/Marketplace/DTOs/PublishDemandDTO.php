<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class PublishDemandDTO
{
    public function __construct(
        public int $buyerId,
        public int $addressId,
        public string $title,
        public ?string $description,
        public string $cropName,
        public float $quantityKg,
        public float $targetPricePerKg,
        public string $neededByDate,
        public string $expiryDate,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $buyerId, array $validated): self
    {
        return new self(
            buyerId: $buyerId,
            addressId: (int) $validated['address_id'],
            title: (string) $validated['title'],
            description: isset($validated['description']) ? (string) $validated['description'] : null,
            cropName: (string) $validated['crop_name'],
            quantityKg: (float) $validated['quantity_kg'],
            targetPricePerKg: (float) $validated['target_price_per_kg'],
            neededByDate: (string) $validated['needed_by_date'],
            expiryDate: (string) $validated['expiry_date'],
        );
    }
}
