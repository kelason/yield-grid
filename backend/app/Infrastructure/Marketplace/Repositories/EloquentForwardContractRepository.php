<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace\Repositories;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function findById(int $id): ForwardContract
    {
        return ForwardContract::with('moderationRoot')->findOrFail($id);
    }

    public function findModerationRootLocked(int $id): ForwardContract
    {
        $rootId = ForwardContract::whereKey($id)->value('moderation_root_id') ?? $id;

        return ForwardContract::whereKey((int) $rootId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return Builder<ForwardContract>
     */
    public function queryPublicItems(): Builder
    {
        return ForwardContract::available()->visible();
    }

    /**
     * @param  array{search?: ?string, visibility?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, ForwardContract>
     */
    public function paginateForAdmin(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ForwardContract::query()->with(['farmer', 'moderationRoot']);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%'.$this->escapeLike($search).'%';
            $query->where(function (Builder $nested) use ($like): void {
                $nested->where('title', 'ilike', $like)->orWhere('crop_name', 'ilike', $like);
            });
        }

        if (($filters['visibility'] ?? null) === 'visible') {
            $query->visible();
        } elseif (($filters['visibility'] ?? null) === 'hidden') {
            $query->hidden();
        }

        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @return array{total: int, visible: int, hidden: int}
     */
    public function visibilityCounts(): array
    {
        $total = ForwardContract::query()->count();
        $visible = ForwardContract::query()->visible()->count();

        return ['total' => $total, 'visible' => $visible, 'hidden' => $total - $visible];
    }

    private function escapeLike(string $search): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    }
}
