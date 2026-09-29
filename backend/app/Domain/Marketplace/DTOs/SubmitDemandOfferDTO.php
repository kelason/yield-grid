<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class SubmitDemandOfferDTO
{
    public function __construct(
        public int $demandId,
        public int $farmerId,
        public float $quantityKg,
        public float $pricePerKg,
        public ?string $message = null,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $demandId, int $farmerId, array $validated): self
    {
        return new self(
            demandId: $demandId,
            farmerId: $farmerId,
            quantityKg: (float) $validated['quantity_kg'],
            pricePerKg: (float) $validated['price_per_kg'],
            message: isset($validated['message']) ? (string) $validated['message'] : null,
        );
    }
}
