<?php

namespace Domain\Farming\DTOs;

readonly class CreatePlotDTO
{
    /**
     * @param array<int, array{float, float}> $coordinates
     */
    public function __construct(
        public int $farmId,
        public string $name,
        public ?string $soilType,
        public array $coordinates, 
    ) {}

    public static function fromRequest(array $validated, int $farmId): self
    {
        return new self(
            farmId: $farmId,
            name: $validated['name'],
            soilType: $validated['soil_type'] ?? null,
            coordinates: $validated['coordinates'],
        );
    }
}
