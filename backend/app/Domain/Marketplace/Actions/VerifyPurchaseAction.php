<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\ContractPurchased;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use Exception;
use Illuminate\Support\Facades\DB;

final class VerifyPurchaseAction
{
    public function __construct(
        private readonly PayMongoService $payMongoService
    ) {}

    /**
     * Actively verify a pending purchase with PayMongo and mark it as completed if paid.
     *
     * @return Purchase The potentially updated purchase model
     */
    public function execute(Purchase $purchase): Purchase
    {
        if ($purchase->payment_status === PaymentStatus::COMPLETED) {
            return $purchase;
        }

        try {
            $sessionData = $this->payMongoService->getCheckoutSession($purchase->paymongo_checkout_id);

            // Check for payments array
            $payments = $sessionData['data']['attributes']['payments'] ?? [];
            $isPaid = false;
            $paymentIntentId = null;
            $paymentMethodString = 'card';

            foreach ($payments as $payment) {
                if (($payment['attributes']['status'] ?? '') === 'paid') {
                    $isPaid = true;
                    $paymentIntentId = $payment['id'] ?? null;
                    $paymentMethodString = $payment['attributes']['source']['type'] ?? 'card';
                    break;
                }
            }

            // Fallback to checking payment_intent
            $paymentIntent = $sessionData['data']['attributes']['payment_intent'] ?? null;
            if (! $isPaid && $paymentIntent && ($paymentIntent['attributes']['status'] ?? '') === 'succeeded') {
                $isPaid = true;
                $paymentIntentId = $paymentIntent['id'] ?? null;
                $paymentMethodString = $sessionData['data']['attributes']['payment_method_used'] ?? 'card';
            }

            if ($isPaid && $purchase->payment_status === PaymentStatus::PENDING) {
                DB::transaction(function () use ($purchase, $paymentIntentId, $paymentMethodString) {
                    $contract = ForwardContract::lockForUpdate()->findOrFail($purchase->forward_contract_id);
                    $paymentMethod = PaymentMethod::tryFrom($paymentMethodString) ?? PaymentMethod::CARD;

                    $purchase->update([
                        'paymongo_payment_id' => $paymentIntentId,
                        'payment_method' => $paymentMethod,
                        'payment_status' => PaymentStatus::COMPLETED,
                        'purchased_at' => now(),
                    ]);

                    $contract->update(['status' => ContractStatus::SOLD]);
                    broadcast(new ContractPurchased($purchase, $contract))->toOthers();
                });
            }
        } catch (Exception $e) {
            // Silently fail, let webhook handle it later or log it in the caller
        }

        return $purchase->fresh();
    }
}
