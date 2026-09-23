<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Services;

use Illuminate\Database\Eloquent\Model;

interface PaymentGatewayInterface
{
    /**
     * @return array{checkout_url: string, checkout_id: string}
     */
    public function createCheckoutSession(
        Model $contract,
        string $successUrl,
        string $cancelUrl,
        int $buyerId,
        ?float $customAmount = null
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function getCheckoutSession(string $sessionId): array;

    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool;

    /**
     * @return array<string, mixed>
     */
    public function parseWebhookEvent(string $payload): array;
}
