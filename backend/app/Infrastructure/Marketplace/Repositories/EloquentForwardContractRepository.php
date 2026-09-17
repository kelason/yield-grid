<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;

class EloquentForwardContractRepository implements ForwardContractRepositoryInterface
{
    public function create(array $data): ForwardContract
    {
        return ForwardContract::create($data);
    }

    public function getFarmerStats(int $farmerId): array
    {
        return [
            'total_listed' => ForwardContract::byFarmer($farmerId)->count(),
            'total_sold' => ForwardContract::byFarmer($farmerId)->where('status', 'sold')->count(),
            'total_reserved' => ForwardContract::byFarmer($farmerId)->where('status', 'reserved')->count(),
            'total_revenue' => ForwardContract::byFarmer($farmerId)->where('status', 'sold')->sum('total_price'),
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
