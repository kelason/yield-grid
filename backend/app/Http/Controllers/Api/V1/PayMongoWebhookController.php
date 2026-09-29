<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Constants\PaymentConstants;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\ContractPurchased;
use App\Domain\Marketplace\Events\DemandOfferPaid;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Marketplace\Services\PayMongoService;
use Illuminate\Database\Eloquent\Model;
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

        $result = DB::transaction(function () use ($checkoutId, $paymentIntentId, $paymentMethod, &$purchase, &$purchasable) {
            $purchase = Purchase::where('paymongo_checkout_id', $checkoutId)->lockForUpdate()->first();

            if (! $purchase) {
                Log::warning('Purchase not found for checkout ID: '.$checkoutId);

                return response('Purchase not found', HttpCode::OK);
            }

            if ($purchase->payment_status === PaymentStatus::COMPLETED) {
                return response('Already processed', HttpCode::OK);
            }

            $purchasable = $this->lockPurchasable($purchase);

            if (! $purchasable) {
                Log::warning('Purchasable not found for purchase: '.$purchase->id);

                return response('Purchasable not found', HttpCode::OK);
            }

            $purchase->update([
                'paymongo_payment_id' => $paymentIntentId,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::COMPLETED,
                'purchased_at' => now(),
            ]);

            if ($purchasable instanceof CropDemandOffer) {
                $purchasable->update([
                    'status' => $purchase->is_downpayment ? DemandOfferStatus::PARTIALLY_PAID : DemandOfferStatus::PAID,
                    'paid_at' => now(),
                ]);
            } else {
                $purchasable->update(['status' => ContractStatus::SOLD]);
            }

            return null; // Signals success
        });

        if ($result !== null) {
            return $result;
        }

        try {
            if ($purchasable instanceof CropDemandOffer) {
                broadcast(new DemandOfferPaid($purchase, $purchasable->load('demand')))->toOthers();
            } else {
                broadcast(new ContractPurchased($purchase, $purchasable))->toOthers();
            }
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
                $this->releasePurchasable($purchase);
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
                $this->releasePurchasable($purchase);
            });
        }

        return response('OK', HttpCode::OK);
    }

    /**
     * Lock and return the purchased item, regardless of its type.
     */
    private function lockPurchasable(Purchase $purchase): ForwardContract|HarvestListing|CropDemandOffer|null
    {
        if ($purchase->forward_contract_id !== null) {
            return ForwardContract::lockForUpdate()->find($purchase->forward_contract_id);
        }

        if ($purchase->harvest_listing_id !== null) {
            return HarvestListing::lockForUpdate()->find($purchase->harvest_listing_id);
        }

        if ($purchase->crop_demand_offer_id !== null) {
            return CropDemandOffer::lockForUpdate()->find($purchase->crop_demand_offer_id);
        }

        return null;
    }

    /**
     * Release a reserved contract/listing back to available. Demand offers
     * stay accepted so the buyer can retry payment or cancel the offer.
     */
    private function releasePurchasable(Purchase $purchase): void
    {
        $purchasable = $this->lockPurchasable($purchase);

        if ($purchasable instanceof Model && ! $purchasable instanceof CropDemandOffer) {
            $purchasable->update(['status' => ContractStatus::AVAILABLE]);
        }
    }
}
