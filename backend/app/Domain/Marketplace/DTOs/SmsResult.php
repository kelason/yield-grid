<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\DTOs;

final readonly class SmsResult
{
    public function __construct(
        public bool $sent,
        public string $provider,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {}
}
