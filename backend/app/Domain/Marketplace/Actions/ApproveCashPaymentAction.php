<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\CashPaymentApproved;
use App\Domain\Marketplace\Events\DemandOfferPaid;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Shared\Database\TransactionManagerInterface;
use Carbon\Carbon;
use LogicException;

final class ApproveCashPaymentAction
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(Purchase $purchase, string $type, ?float $amount = null): Purchase
    {
        $purchase = $this->transactionManager->run(function () use ($purchase, $type, $amount) {
            $purchase = Purchase::where('id', $purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->payment_status === PaymentStatus::COMPLETED) {
                throw new LogicException('Purchase is already fully paid.');
            }

            if ($type === 'partial') {
                if ($amount === null || $amount <= 0) {
                    throw new LogicException('Amount is required for partial payment.');
                }
                $outstanding = (float) $purchase->total_contract_amount - (float) $purchase->cash_amount_confirmed;
                if ($amount > $outstanding) {
                    throw new LogicException('Amount exceeds the outstanding balance of ₱'.number_format($outstanding, 2).'.');
                }
                $purchase->cash_payment_status = CashPaymentStatus::PARTIALLY_PAID;
                $purchase->cash_amount_confirmed += $amount;
                $purchase->amount_paid = $purchase->cash_amount_confirmed;
            } elseif ($type === 'full') {
                $purchase->cash_payment_status = CashPaymentStatus::FULLY_PAID;
                $purchase->cash_amount_confirmed = (float) $purchase->total_contract_amount;
                $purchase->amount_paid = (float) $purchase->total_contract_amount;
                $purchase->payment_status = PaymentStatus::COMPLETED;
                $purchase->purchased_at = Carbon::now();
            }

            $purchase->farmer_confirmed_at = Carbon::now();
            $purchase->save();

            // Update contract/listing/offer status to reflect payment state
            $purchasable = $purchase->contract ?? $purchase->harvestListing ?? $purchase->demandOffer;
            if ($purchasable) {
                $purchasableClass = get_class($purchasable);
                $lockedPurchasable = $purchasableClass::where('id', $purchasable->id)->lockForUpdate()->firstOrFail();

                if ($lockedPurchasable instanceof CropDemandOffer) {
                    if ($type === 'full') {
                        $lockedPurchasable->status = DemandOfferStatus::PAID;
                        $lockedPurchasable->paid_at = Carbon::now();
                        $lockedPurchasable->save();
                    } else {
                        $lockedPurchasable->status = DemandOfferStatus::PARTIALLY_PAID;
                        $lockedPurchasable->save();
                    }
                } else {
                    $lockedPurchasable->status = $type === 'full'
                        ? ContractStatus::SOLD
                        : ContractStatus::PARTIALLY_PAID;
                    $lockedPurchasable->save();
                }
            }

            return $purchase;
        });

        CashPaymentApproved::dispatch($purchase);

        if ($purchase->demandOffer !== null && $purchase->payment_status === PaymentStatus::COMPLETED) {
            try {
                broadcast(new DemandOfferPaid($purchase, $purchase->demandOffer->load('demand')))->toOthers();
            } catch (\Throwable) {
            }
        }

        return $purchase;
    }
}
