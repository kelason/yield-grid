<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Repositories;

use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseRepositoryInterface
{
    /**
     * Get paginated purchases for a specific buyer, with optional filters and sorting.
     *
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function getBuyerPurchases(int $buyerId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function update(Purchase $purchase, array $data): bool;

    /**
     * Find a purchase by id with a row-level write lock.
     * Must be called inside a transaction.
     */
    public function findLockedById(int $id): Purchase;

    public function findByCheckoutId(string $checkoutId, int $buyerId): Purchase;

    public function findPendingByCheckoutId(string $checkoutId, int $buyerId): ?Purchase;

    /**
     * Get completed-purchase stats for a specific buyer.
     *
     * @return array{total_purchases: int, total_spent: float}
     */
    public function getBuyerStats(int $buyerId): array;
}
