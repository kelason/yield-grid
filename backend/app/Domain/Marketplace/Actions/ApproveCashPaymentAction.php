<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\CashPaymentApproved;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Shared\Database\TransactionManagerInterface;
use Carbon\Carbon;
use RuntimeException;

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
                throw new RuntimeException('Purchase is already fully paid.');
            }

            if ($type === 'partial') {
                if ($amount === null || $amount <= 0) {
                    throw new RuntimeException('Amount is required for partial payment.');
                }
                $purchase->cash_payment_status = CashPaymentStatus::PARTIALLY_PAID;
                $purchase->cash_amount_confirmed += $amount;
            } elseif ($type === 'full') {
                $purchase->cash_payment_status = CashPaymentStatus::FULLY_PAID;
                $purchase->cash_amount_confirmed = (float) $purchase->total_contract_amount;
                $purchase->payment_status = PaymentStatus::COMPLETED;
                $purchase->purchased_at = Carbon::now();
            }

            $purchase->farmer_confirmed_at = Carbon::now();
            $purchase->save();

            // Update contract status if fully paid
            if ($type === 'full') {
                $purchasable = $purchase->contract ?? $purchase->harvestListing;
                if ($purchasable) {
                    $purchasableClass = get_class($purchasable);
                    $lockedPurchasable = $purchasableClass::where('id', $purchasable->id)->lockForUpdate()->firstOrFail();
                    $lockedPurchasable->status = ContractStatus::SOLD;
                    $lockedPurchasable->save();
                }
            }

            // Here we would dispatch CashPaymentApproved event (Phase 7)

            return $purchase;
        });

        CashPaymentApproved::dispatch($purchase);

        return $purchase;
    }
}
