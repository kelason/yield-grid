<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseRepositoryInterface
{
    /**
     * Get paginated purchases for a specific buyer, with optional filters and sorting.
     *
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function getBuyerPurchases(int $buyerId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
