<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class DaPriceSyncResult
{
    public function __construct(
        public string $status,
        public int $rowsUpserted = 0,
        public ?string $error = null,
        public bool $usedAi = false,
    ) {}
}
