<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

use App\Domain\Marketplace\Enums\PriceMatchType;

final readonly class CropNameResolution
{
    public function __construct(
        public string $slug,
        public string $displayName,
        public PriceMatchType $matchType,
        public ?string $correctedFrom = null,
    ) {}
}
