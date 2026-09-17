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

    public function findByCheckoutId(string $checkoutId, int $buyerId): Purchase;

    public function findPendingByCheckoutId(string $checkoutId, int $buyerId): ?Purchase;
}
