<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;

class EloquentForwardContractRepository implements ForwardContractRepositoryInterface
{
    public function create(array $data): ForwardContract
    {
        return ForwardContract::create($data);
    }

    public function getFarmerStats(int $farmerId): array
    {
        $contractsListed = ForwardContract::byFarmer($farmerId)->count();
        $contractsSold = ForwardContract::byFarmer($farmerId)->where('status', ContractStatus::SOLD->value)->count();
        $contractsReserved = ForwardContract::byFarmer($farmerId)->where('status', ContractStatus::RESERVED->value)->count();
        $contractsRevenue = ForwardContract::byFarmer($farmerId)->where('status', ContractStatus::SOLD->value)->sum('total_price');

        $listingsListed = HarvestListing::byFarmer($farmerId)->count();
        $listingsSold = HarvestListing::byFarmer($farmerId)->where('status', ContractStatus::SOLD->value)->count();
        $listingsReserved = HarvestListing::byFarmer($farmerId)->where('status', ContractStatus::RESERVED->value)->count();
        $listingsRevenue = HarvestListing::byFarmer($farmerId)->where('status', ContractStatus::SOLD->value)->sum('total_price');

        return [
            'total_listed' => $contractsListed + $listingsListed,
            'total_sold' => $contractsSold + $listingsSold,
            'total_reserved' => $contractsReserved + $listingsReserved,
            'total_revenue' => $contractsRevenue + $listingsRevenue,
        ];
    }

    public function findByIdLocked(int $id): ForwardContract
    {
        return ForwardContract::lockForUpdate()->findOrFail($id);
    }

    public function update(ForwardContract $contract, array $data): bool
    {
        return $contract->update($data);
    }
}
