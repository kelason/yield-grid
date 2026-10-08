<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Repositories;

use App\Domain\Marketplace\Models\ForwardContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface ForwardContractRepositoryInterface
{
    public function create(array $data): ForwardContract;

    public function getFarmerStats(int $farmerId): array;

    public function findByIdLocked(int $id): ForwardContract;

    public function update(ForwardContract $contract, array $data): bool;

    public function findById(int $id): ForwardContract;

    /**
     * Lock the moderation root for the given contract. Callers must take
     * this lock before locking the contract row itself (root-before-item
     * order). Requires an enclosing transaction.
     */
    public function findModerationRootLocked(int $id): ForwardContract;

    /**
     * Base public catalog query: available and effectively visible.
     *
     * @return Builder<ForwardContract>
     */
    public function queryPublicItems(): Builder;

    /**
     * @param  array{search?: ?string, visibility?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, ForwardContract>
     */
    public function paginateForAdmin(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Database-wide record counts using the same effective (root-aware)
     * visibility scopes as the admin list. Split clones and roots all count
     * as records; root-suppressed clones count as hidden.
     *
     * @return array{total: int, visible: int, hidden: int}
     */
    public function visibilityCounts(): array;
}
