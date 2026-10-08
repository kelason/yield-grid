<?php

declare(strict_types=1);

namespace App\Infrastructure\Contact\Repositories;

use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentIssueTicketRepository implements IssueTicketRepositoryInterface
{
    public function findForReporter(int $id, int $userId): IssueTicket
    {
        return IssueTicket::where('id', $id)->where('user_id', $userId)->firstOrFail();
    }

    public function findLockedById(int $id): IssueTicket
    {
        return IssueTicket::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function findById(int $id): IssueTicket
    {
        return IssueTicket::with(['reporter', 'resolver'])->where('id', $id)->firstOrFail();
    }

    public function findByRequestId(int $userId, string $requestId): ?IssueTicket
    {
        return IssueTicket::where('user_id', $userId)
            ->where('client_request_id', $requestId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): IssueTicket
    {
        return IssueTicket::create($attributes);
    }

    public function save(IssueTicket $issue): void
    {
        $issue->save();
    }

    /**
     * @param  array{status?: string}  $filters
     * @return LengthAwarePaginator<int, IssueTicket>
     */
    public function paginateForReporter(int $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = IssueTicket::where('user_id', $userId)->orderByDesc('created_at')->orderByDesc('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array{status?: string, category?: string, search?: string}  $filters
     * @return LengthAwarePaginator<int, IssueTicket>
     */
    public function paginateForAdmin(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = IssueTicket::query()->with(['reporter', 'resolver'])
            ->orderByDesc('created_at')->orderByDesc('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search'])) {
            $like = '%'.$this->escapeLike($filters['search']).'%';
            $query->where(function (Builder $nested) use ($like): void {
                $nested->where('subject', 'like', $like)->orWhere('description', 'like', $like);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        $counts = [];

        foreach (IssueStatus::cases() as $status) {
            $counts[$status->value] = IssueTicket::where('status', $status->value)->count();
        }

        return $counts;
    }

    private function escapeLike(string $search): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    }
}
