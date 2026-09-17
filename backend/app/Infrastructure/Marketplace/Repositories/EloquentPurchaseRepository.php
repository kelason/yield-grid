<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\Purchase;
use App\Domain\Marketplace\Repositories\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPurchaseRepository implements PurchaseRepositoryInterface
{
    /**
     * Get paginated purchases for a specific buyer, with optional filters and sorting.
     *
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function getBuyerPurchases(int $buyerId, array $filters = [], int $perPage = PaginationConstants::PURCHASES_PER_PAGE): LengthAwarePaginator
    {
        $query = Purchase::where('buyer_id', $buyerId)
            ->with(['contract.farmer.farms']);

        if (! empty($filters['search'])) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->where('crop_name', 'ilike', "%{$filters['search']}%");
            });
        }

        match ($filters['sort'] ?? null) {
            'oldest' => $query->oldest(),
            'highest_price' => $query->orderByDesc('amount_paid'),
            'lowest_price' => $query->orderBy('amount_paid'),
            default => $query->latest(),
        };

        return $query->paginate($perPage);
    }

    public function update(Purchase $purchase, array $data): bool
    {
        return $purchase->update($data);
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
}
