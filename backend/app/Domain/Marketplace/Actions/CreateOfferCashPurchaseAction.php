<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\CashPaymentRequested;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Shared\Database\TransactionManagerInterface;
use RuntimeException;

final class CreateOfferCashPurchaseAction
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(CropDemandOffer $offer, int $buyerId): Purchase
    {
        $purchase = $this->transactionManager->run(function () use ($offer, $buyerId) {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if ($lockedOffer->status !== DemandOfferStatus::ACCEPTED) {
                throw new RuntimeException('This offer is no longer payable.');
            }

            $hasActivePurchase = Purchase::where('crop_demand_offer_id', $lockedOffer->id)
                ->whereIn('payment_status', [PaymentStatus::PENDING, PaymentStatus::COMPLETED])
                ->exists();

            if ($hasActivePurchase) {
                throw new RuntimeException('This offer already has an active payment.');
            }

            $lockedOffer->loadMissing('demand');
            $isDownpayment = $lockedOffer->demand->needed_by_date->isFuture();
            $totalContractAmount = (float) $lockedOffer->total_price;
            $amountPaid = $isDownpayment ? $totalContractAmount * PaymentConstants::DOWNPAYMENT_PERCENTAGE : $totalContractAmount;

            return Purchase::create([
                'buyer_id' => $buyerId,
                'crop_demand_offer_id' => $lockedOffer->id,
                'quantity_kg' => $lockedOffer->quantity_kg,
                'payment_method' => PaymentMethod::CASH,
                'amount_paid' => $amountPaid,
                'currency' => $lockedOffer->currency,
                'payment_status' => PaymentStatus::PENDING,
                'cash_payment_status' => CashPaymentStatus::PENDING_APPROVAL,
                'is_downpayment' => $isDownpayment,
                'total_contract_amount' => $totalContractAmount,
            ]);
        });

        CashPaymentRequested::dispatch($purchase);

        return $purchase;
    }
}
