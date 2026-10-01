<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Services;

use App\Domain\Marketplace\DTOs\SmsResult;

interface SmsServiceInterface
{
    public function isConfigured(): bool;

    public function send(string $to, string $message): SmsResult;
}
