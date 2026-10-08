<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Enums\CashPaymentStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPurchaseRepository implements PurchaseRepositoryInterface
{
    private const STATUS_PARTIALLY_PAID = 'partially_paid';

    private const STATUS_PAID = 'paid';

    /**
     * Get paginated purchases for a specific buyer, with optional filters and sorting.
     *
     * @param  array{search?: string, sort?: string, status?: string}  $filters
     */
    public function getBuyerPurchases(int $buyerId, array $filters = [], int $perPage = PaginationConstants::PURCHASES_PER_PAGE): LengthAwarePaginator
    {
        $query = Purchase::where('buyer_id', $buyerId)
            ->with(['contract.farmer.farms', 'contract.moderationRoot', 'harvestListing.farmer.farms', 'harvestListing.moderationRoot', 'demandOffer.demand', 'demandOffer.farmer']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn ($qq) => $qq->where('crop_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('harvestListing', fn ($qq) => $qq->where('crop_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('demandOffer.demand', fn ($qq) => $qq->where('crop_name', 'ilike', "%{$search}%"));
            });
        }

        match ($filters['sort'] ?? null) {
            'oldest' => $query->oldest(),
            'highest_price' => $query->orderByDesc('amount_paid'),
            'lowest_price' => $query->orderBy('amount_paid'),
            default => $query->latest(),
        };

        // Display statuses mirror PurchaseCard: partially paid covers completed
        // downpayments with an outstanding balance plus cash partial approvals.
        match ($filters['status'] ?? null) {
            PaymentStatus::PENDING->value => $query->where('payment_status', PaymentStatus::PENDING)
                ->where(fn ($q) => $q->whereNull('cash_payment_status')->orWhere('cash_payment_status', '!=', CashPaymentStatus::PARTIALLY_PAID->value)),
            self::STATUS_PARTIALLY_PAID => $query->where(fn ($q) => $q
                ->where(fn ($qq) => $qq->where('payment_status', PaymentStatus::COMPLETED)
                    ->where('is_downpayment', true)
                    ->where(fn ($qqq) => $qqq->whereNull('total_contract_amount')->orWhereColumn('amount_paid', '<', 'total_contract_amount')))
                ->orWhere('cash_payment_status', CashPaymentStatus::PARTIALLY_PAID->value)),
            self::STATUS_PAID => $query->where('payment_status', PaymentStatus::COMPLETED)
                ->where(fn ($q) => $q->where('is_downpayment', false)
                    ->orWhere(fn ($qq) => $qq->whereNotNull('total_contract_amount')->whereColumn('amount_paid', '>=', 'total_contract_amount'))),
            PaymentStatus::FAILED->value => $query->where('payment_status', PaymentStatus::FAILED),
            default => null,
        };

        return $query->paginate($perPage);
    }

    public function update(Purchase $purchase, array $data): bool
    {
        return $purchase->update($data);
    }

    public function findLockedById(int $id): Purchase
    {
        return Purchase::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function findByCheckoutId(string $checkoutId, int $buyerId): Purchase
    {
        return Purchase::where('paymongo_checkout_id', $checkoutId)
            ->where('buyer_id', $buyerId)
            ->firstOrFail();
    }

    public function findPendingByCheckoutId(string $checkoutId, int $buyerId): ?Purchase
    {
        return Purchase::where('paymongo_checkout_id', $checkoutId)
            ->where('buyer_id', $buyerId)
            ->where('payment_status', PaymentStatus::PENDING)
            ->first();
    }

    public function getBuyerStats(int $buyerId): array
    {
        $completed = Purchase::where('buyer_id', $buyerId)
            ->where('payment_status', PaymentStatus::COMPLETED);

        return [
            'total_purchases' => (clone $completed)->count(),
            'total_spent' => (float) $completed->sum('amount_paid'),
        ];
    }
}
