<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Constants\PaymentConstants;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\ContractPurchased;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\Purchase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PayMongoWebhookController extends Controller
{
    public function __construct(
        private readonly PayMongoService $payMongo
    ) {}

    public function handle(Request $request): Response
    {
        $signature = $request->header('Paymongo-Signature', '');

        if (! $this->payMongo->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('PayMongo webhook signature verification failed');

            return response('Invalid signature', HttpCode::BAD_REQUEST);
        }

        $event = $this->payMongo->parseWebhookEvent($request->getContent());
        $eventType = $event['data']['attributes']['type'] ?? '';

        return match ($eventType) {
            PaymentConstants::EVENT_PAYMENT_PAID => $this->handlePaymentCompleted($event),
            PaymentConstants::EVENT_PAYMENT_FAILED => $this->handlePaymentFailed($event),
            PaymentConstants::EVENT_SESSION_EXPIRED => $this->handleSessionExpired($event),
            default => response('OK', HttpCode::OK),
        };
    }

    private function handlePaymentCompleted(array $event): Response
    {
        $checkoutData = $event['data']['attributes']['data']['attributes'] ?? [];
        $checkoutId = $event['data']['attributes']['data']['id'] ?? null;
        $paymentIntentId = $checkoutData['payment_intent']['id'] ?? null;

        // Some methods like GCash don't expose raw method name easily in checkout payload
        // We'll fall back to 'card' or infer from intent if possible
        $paymentMethodString = $checkoutData['payment_method_used'] ?? 'card';
        $paymentMethod = PaymentMethod::tryFrom($paymentMethodString) ?? PaymentMethod::CARD;

        if (! $checkoutId) {
            Log::warning('PayMongo webhook missing checkout ID');

            return response('Missing checkout ID', HttpCode::OK); // Return 200 to prevent retries
        }

        $result = DB::transaction(function () use ($checkoutId, $paymentIntentId, $paymentMethod, &$purchase, &$contract) {
            $purchase = Purchase::where('paymongo_checkout_id', $checkoutId)->lockForUpdate()->first();

            if (! $purchase) {
                Log::warning('Purchase not found for checkout ID: '.$checkoutId);

                return response('Purchase not found', HttpCode::OK);
            }

            if ($purchase->payment_status === PaymentStatus::COMPLETED) {
                return response('Already processed', HttpCode::OK);
            }

            $contract = ForwardContract::lockForUpdate()->findOrFail($purchase->forward_contract_id);

            $purchase->update([
                'paymongo_payment_id' => $paymentIntentId,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::COMPLETED,
                'purchased_at' => now(),
            ]);

            $contract->update(['status' => ContractStatus::SOLD]);

            return null; // Signals success
        });

        if ($result !== null) {
            return $result;
        }

        try {
            broadcast(new ContractPurchased($purchase, $contract))->toOthers();
        } catch (\Exception $e) {
            Log::error('Webhook broadcast failed: '.$e->getMessage());
        }

        return response('OK', HttpCode::OK);
    }

    private function handlePaymentFailed(array $event): Response
    {
        $checkoutId = $event['data']['attributes']['data']['id'] ?? null;
        if (! $checkoutId) {
            return response('OK', HttpCode::OK);
        }

        $purchase = Purchase::where('paymongo_checkout_id', $checkoutId)->first();
        if ($purchase && $purchase->payment_status === PaymentStatus::PENDING) {
            DB::transaction(function () use ($purchase): void {
                $purchase->update(['payment_status' => PaymentStatus::FAILED]);
                ForwardContract::where('id', $purchase->forward_contract_id)
                    ->update(['status' => ContractStatus::AVAILABLE]);
            });
        }

        return response('OK', HttpCode::OK);
    }

    private function handleSessionExpired(array $event): Response
    {
        $checkoutId = $event['data']['attributes']['data']['id'] ?? null;
        if (! $checkoutId) {
            return response('OK', HttpCode::OK);
        }

        $purchase = Purchase::where('paymongo_checkout_id', $checkoutId)->first();
        if ($purchase && $purchase->payment_status === PaymentStatus::PENDING) {
            DB::transaction(function () use ($purchase): void {
                $purchase->update(['payment_status' => PaymentStatus::EXPIRED]);
                ForwardContract::where('id', $purchase->forward_contract_id)
                    ->update(['status' => ContractStatus::AVAILABLE]);
            });
        }

        return response('OK', HttpCode::OK);
    }
}
