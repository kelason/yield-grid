<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Constants\ReportingConstants;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Shared\Enums\ReportTargetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Allowlisted admin pagination over the five reportable content types.
 *
 * Visibility filters track the direct hidden flag for forum and demand
 * rows. Contract and listing rows use the repository-backed effective
 * (root-aware) visibility scopes.
 */
final class AdminContentPaginator
{
    public function __construct(
        private readonly ForwardContractRepositoryInterface $contracts,
    ) {}

    /**
     * @param  array{search?: ?string, visibility?: ?string}  $filters
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(ReportTargetType $type, array $filters, int $perPage): LengthAwarePaginator
    {
        $paginator = match ($type) {
            ReportTargetType::THREAD => $this->paginateThreads($filters, $perPage),
            ReportTargetType::REPLY => $this->paginateReplies($filters, $perPage),
            ReportTargetType::CONTRACT => $this->contracts->paginateForAdmin($this->contractFilters($filters), $perPage),
            ReportTargetType::LISTING => $this->paginateListings($filters, $perPage),
            ReportTargetType::DEMAND => $this->paginateDemands($filters, $perPage),
        };

        assert($paginator instanceof LengthAwarePaginator);

        return $paginator;
    }

    /**
     * @param  array{search?: ?string, visibility?: ?string}  $filters
     * @return LengthAwarePaginator<int, ForumThread>
     */
    private function paginateThreads(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ForumThread::query()->with('author');

        $this->applySearch($query, (string) ($filters['search'] ?? ''), ['title', 'body']);
        $this->applyDirectVisibility($query, $filters['visibility'] ?? null, 'forum_threads');

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array{search?: ?string, visibility?: ?string}  $filters
     * @return LengthAwarePaginator<int, ForumReply>
     */
    private function paginateReplies(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ForumReply::query()->with(['author', 'thread']);

        $this->applySearch($query, (string) ($filters['search'] ?? ''), ['body']);
        $this->applyDirectVisibility($query, $filters['visibility'] ?? null, 'forum_replies');

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array{search?: ?string, visibility?: ?string}  $filters
     * @return LengthAwarePaginator<int, HarvestListing>
     */
    private function paginateListings(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = HarvestListing::query()->with(['farmer', 'moderationRoot']);

        $this->applySearch($query, (string) ($filters['search'] ?? ''), ['title', 'crop_name']);

        if (($filters['visibility'] ?? null) === ReportingConstants::VISIBILITY_VISIBLE) {
            $query->visible();
        } elseif (($filters['visibility'] ?? null) === ReportingConstants::VISIBILITY_HIDDEN) {
            $query->hidden();
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array{search?: ?string, visibility?: ?string}  $filters
     * @return LengthAwarePaginator<int, CropDemand>
     */
    private function paginateDemands(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = CropDemand::query()->with('buyer');

        $this->applySearch($query, (string) ($filters['search'] ?? ''), ['title', 'crop_name']);
        $this->applyDirectVisibility($query, $filters['visibility'] ?? null, 'crop_demands');

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array{search?: ?string, visibility?: ?string}  $filters
     * @return array{search?: ?string, visibility?: ?string, status?: ?string}
     */
    private function contractFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? null,
            'visibility' => $filters['visibility'] ?? null,
            'status' => null,
        ];
    }

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @param  array<int, string>  $columns
     */
    private function applySearch(Builder $query, string $search, array $columns): void
    {
        $search = trim($search);

        if ($search === '' || $columns === []) {
            return;
        }

        $like = '%'.$this->escapeLike($search).'%';

        $query->where(function (Builder $nested) use ($like, $columns): void {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $nested->where($column, 'ilike', $like);
                } else {
                    $nested->orWhere($column, 'ilike', $like);
                }
            }
        });
    }

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     */
    private function applyDirectVisibility(Builder $query, ?string $visibility, string $table): void
    {
        if ($visibility === ReportingConstants::VISIBILITY_VISIBLE) {
            $query->whereNull("{$table}.hidden_at");
        } elseif ($visibility === ReportingConstants::VISIBILITY_HIDDEN) {
            $query->whereNotNull("{$table}.hidden_at");
        }
    }

    private function escapeLike(string $search): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    }
}
