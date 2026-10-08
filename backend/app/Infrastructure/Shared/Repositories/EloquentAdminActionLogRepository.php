<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Repositories;

use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;

final class EloquentAdminActionLogRepository implements AdminActionLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function append(
        ?int $actorId,
        AdminAction $action,
        string $subjectType,
        string $subjectId,
        string $reason,
        array $before,
        array $after,
        ?int $reportId = null,
    ): AdminActionLog {
        return AdminActionLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
            'related_report_id' => $reportId,
        ]);
    }
}
