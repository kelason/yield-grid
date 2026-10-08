<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Models\AdminActionLog;

interface AdminActionLogRepositoryInterface
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
    ): AdminActionLog;
}
