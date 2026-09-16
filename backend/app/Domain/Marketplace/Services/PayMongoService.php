<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Services;

use App\Constants\PaymentConstants;
use App\Domain\Marketplace\Models\ForwardContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PayMongoService
{
    private string $baseUrl;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com/v1');
        $this->secretKey = config('services.paymongo.secret_key', '');
        $this->webhookSecret = config('services.paymongo.webhook_secret', '');
    }

    /**
     * Create a PayMongo Checkout Session for a forward contract purchase.
     *
     * @return array{checkout_url: string, checkout_id: string}
     */
    public function createCheckoutSession(
        ForwardContract $contract,
        string $successUrl,
        string $cancelUrl,
        int $buyerId
    ): array {
        $amountInCentavos = (int) round((float) $contract->total_price * PaymentConstants::CENTAVO_MULTIPLIER);

        $payload = [
            'data' => [
                'attributes' => [
                    'line_items' => [
                        [
                            'name' => "Forward Contract: {$contract->quantity_kg}kg {$contract->crop_name}",
                            'quantity' => 1,
                            'amount' => $amountInCentavos,
                            'currency' => PaymentConstants::DEFAULT_CURRENCY,
                        ]
                    ],
                    'payment_method_types' => ['card', 'paymaya', 'gcash', 'qrph'],
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'description' => "Forward Contract Purchase - YieldGrid",
                    'metadata' => [
                        'forward_contract_id' => (string) $contract->id,
                        'buyer_id' => (string) $buyerId,
                    ],
                ]
            ]
        ];

        $response = Http::withToken(base64_encode($this->secretKey . ':'))
            ->post("{$this->baseUrl}/checkout_sessions", $payload);

        if ($response->failed()) {
            Log::error('PayMongo checkout session creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'contract_id' => $contract->id,
            ]);
            throw new \RuntimeException('Failed to create payment session with PayMongo.');
        }

        $data = $response->json();

        return [
            'checkout_url' => $data['data']['attributes']['checkout_url'],
            'checkout_id' => $data['data']['id'],
        ];
    }

    /**
     * Verify PayMongo webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        // PayMongo sends signature in format: t=1612345678,te=signature_hash,li=test_signature_hash
        // We extract the timestamp and signature hash to verify
        
        $parts = explode(',', $signatureHeader);
        $timestamp = '';
        $testSignature = '';
        $liveSignature = '';

        foreach ($parts as $part) {
            $keyValue = explode('=', $part, 2);
            if (count($keyValue) !== 2) {
                continue;
            }
            
            [$key, $value] = $keyValue;
            
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'te') {
                $testSignature = $value;
            } elseif ($key === 'li') {
                $liveSignature = $value;
            }
        }

        if (empty($timestamp) || (empty($testSignature) && empty($liveSignature))) {
            return false;
        }

        // Check if timestamp is within tolerance (e.g., 5 minutes)
        if (abs(time() - (int) $timestamp) > PaymentConstants::WEBHOOK_TOLERANCE_SECONDS) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        // We accept either the test or live signature depending on the environment mode
        return hash_equals($expectedSignature, $testSignature) || hash_equals($expectedSignature, $liveSignature);
    }

    /**
     * Parse webhook event payload.
     */
    public function parseWebhookEvent(string $payload): array
    {
        return json_decode($payload, true) ?? [];
    }
}
