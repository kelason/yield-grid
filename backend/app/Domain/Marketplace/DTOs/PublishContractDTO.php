<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class PublishContractDTO
{
    public function __construct(
        public int $recommendationId,
        public int $farmerId,
        public string $title,
        public ?string $description,
        public float $quantityKg,
        public float $pricePerKg,
        public string $estimatedHarvestDate,
        public string $expiryDate,
    ) {}
}
