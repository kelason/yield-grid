<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class CreateHarvestListingDTO
{
    public function __construct(
        public int $farmerId,
        public string $title,
        public ?string $description,
        public string $cropName,
        public float $quantityKg,
        public float $pricePerKg,
        public ?string $estimatedHarvestDate,
        public int $shelfLifeDays,
        public bool $isHarvestAvailable,
    ) {}
}
