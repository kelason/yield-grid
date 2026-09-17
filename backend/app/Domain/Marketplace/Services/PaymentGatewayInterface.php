<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Services;

interface PaymentGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getCheckoutSession(string $sessionId): array;
}
