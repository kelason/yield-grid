<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentMethod;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Events\CashPaymentRequested;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Shared\Database\TransactionManagerInterface;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class CreateCashPurchaseAction
{
    public function __construct(
        private readonly SplitPurchasableAction $splitPurchasableAction,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(Model $purchasable, int $buyerId, float $quantityKg): Purchase
    {
        $purchase = $this->transactionManager->run(function () use ($purchasable, $buyerId, $quantityKg) {
            // Re-fetch with lock
            $modelClass = get_class($purchasable);
            $lockedItem = $modelClass::where('id', $purchasable->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->status !== ContractStatus::AVAILABLE || $quantityKg > (float) $lockedItem->quantity_kg) {
                throw new RuntimeException('Requested quantity is not available.');
            }

            // Calculate if downpayment applies
            $isDownpayment = false;
            if ($lockedItem instanceof ForwardContract) {
                // Downpayment if harvest is in the future
                $isDownpayment = $lockedItem->estimated_harvest_date->isFuture();
            } elseif ($lockedItem instanceof HarvestListing) {
                // Not available means incoming harvest
                $isDownpayment = ! $lockedItem->is_harvest_available;
            }

            $splitItem = $this->splitPurchasableAction->execute($lockedItem, $quantityKg);
            $splitItem->status = ContractStatus::RESERVED;
            $splitItem->save();

            $totalContractAmount = $splitItem->total_price;
            $amountPaid = $isDownpayment ? $totalContractAmount * 0.10 : $totalContractAmount;

            $purchaseData = [
                'buyer_id' => $buyerId,
                'quantity_kg' => $quantityKg,
                'payment_method' => PaymentMethod::CASH,
                'amount_paid' => $amountPaid,
                'currency' => $splitItem->currency,
                'payment_status' => PaymentStatus::PENDING,
                'cash_payment_status' => CashPaymentStatus::PENDING_APPROVAL,
                'is_downpayment' => $isDownpayment,
                'total_contract_amount' => $totalContractAmount,
            ];

            if ($splitItem instanceof ForwardContract) {
                $purchaseData['forward_contract_id'] = $splitItem->id;
            } else {
                $purchaseData['harvest_listing_id'] = $splitItem->id;
            }

            return Purchase::create($purchaseData);
        });

        CashPaymentRequested::dispatch($purchase);

        return $purchase;
    }
}
