<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Repositories;

use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentContentReportRepository implements ContentReportRepositoryInterface
{
    public function findByReporterTarget(int $userId, ReportTargetType $type, string $id): ?ContentReport
    {
        return ContentReport::where('user_id', $userId)
            ->where('reportable_type', $type->value)
            ->where('reportable_id', (int) $id)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContentReport
    {
        return ContentReport::create($attributes);
    }

    public function findLockedById(int $id): ContentReport
    {
        return ContentReport::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function findById(int $id): ContentReport
    {
        return ContentReport::with(['reporter', 'reviewer'])->where('id', $id)->firstOrFail();
    }

    public function save(ContentReport $report): void
    {
        $report->save();
    }

    /**
     * @param  array{status?: string, reportable_type?: string, reason?: string}  $filters
     * @return LengthAwarePaginator<int, ContentReport>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = ContentReport::query()->with(['reporter', 'reviewer'])->orderBy('created_at')->orderBy('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reportable_type'])) {
            $query->where('reportable_type', $filters['reportable_type']);
        }

        if (isset($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        $counts = [];

        foreach (ContentReportStatus::cases() as $status) {
            $counts[$status->value] = ContentReport::where('status', $status->value)->count();
        }

        return $counts;
    }
}
