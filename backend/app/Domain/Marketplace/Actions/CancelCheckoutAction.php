<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use App\Domain\Shared\Database\TransactionManagerInterface;

final class CancelCheckoutAction
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ForwardContractRepositoryInterface $contractRepository,
        private readonly PurchaseRepositoryInterface $purchaseRepository
    ) {}

    /**
     * Cancel a pending purchase and make the associated contract available again.
     */
    public function execute(Purchase $purchase): void
    {
        $this->transactionManager->run(function () use ($purchase) {
            $this->purchaseRepository->update($purchase, [
                'payment_status' => PaymentStatus::FAILED,
            ]);

            $contract = $this->contractRepository->findByIdLocked($purchase->forward_contract_id);
            $this->contractRepository->update($contract, [
                'status' => ContractStatus::AVAILABLE,
            ]);
        });
    }
}
