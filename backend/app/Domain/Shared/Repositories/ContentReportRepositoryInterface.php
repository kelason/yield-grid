<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface ContentReportRepositoryInterface
{
    public function findByReporterTarget(int $userId, ReportTargetType $type, string $id): ?ContentReport;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContentReport;

    /**
     * Find a report with a row-level write lock.
     * Must be called inside a transaction.
     */
    public function findLockedById(int $id): ContentReport;

    /**
     * Find a report for admin review with reporter and reviewer loaded.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): ContentReport;

    public function save(ContentReport $report): void;

    /**
     * @param  array{status?: string, reportable_type?: string, reason?: string}  $filters
     * @return LengthAwarePaginator<int, ContentReport>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
}
