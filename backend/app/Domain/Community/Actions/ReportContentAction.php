<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Community\Models\ForumReport;

final class ReportContentAction
{
    public function execute(int $userId, string $type, int $id, string $reason, ?string $description): ForumReport
    {
        return ForumReport::firstOrCreate(
            [
                'user_id' => $userId,
                'reportable_type' => $type,
                'reportable_id' => $id,
            ],
            [
                'reason' => $reason,
                'description' => $description,
            ]
        );
    }
}
