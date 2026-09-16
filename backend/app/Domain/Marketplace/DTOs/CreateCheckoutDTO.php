<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class CreateCheckoutDTO
{
    public function __construct(
        public int $contractId,
        public int $buyerId,
        public string $successUrl,
        public string $cancelUrl,
    ) {}
}
