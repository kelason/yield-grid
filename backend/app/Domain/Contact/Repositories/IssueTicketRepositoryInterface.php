<?php

declare(strict_types=1);

namespace App\Domain\Contact\Repositories;

use App\Domain\Contact\Models\IssueTicket;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface IssueTicketRepositoryInterface
{
    /**
     * Find a ticket owned by the reporter.
     *
     * @throws ModelNotFoundException
     */
    public function findForReporter(int $id, int $userId): IssueTicket;

    /**
     * Find a ticket with a row-level write lock.
     * Must be called inside a transaction.
     */
    public function findLockedById(int $id): IssueTicket;

    /**
     * Find a ticket for admin review with reporter and resolver loaded.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): IssueTicket;

    public function findByRequestId(int $userId, string $requestId): ?IssueTicket;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): IssueTicket;

    public function save(IssueTicket $issue): void;

    /**
     * @param  array{status?: string}  $filters
     * @return LengthAwarePaginator<int, IssueTicket>
     */
    public function paginateForReporter(int $userId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * @param  array{status?: string, category?: string, search?: string}  $filters
     * @return LengthAwarePaginator<int, IssueTicket>
     */
    public function paginateForAdmin(array $filters, int $perPage): LengthAwarePaginator;
}
