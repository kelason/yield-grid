<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Shared\Actions\SubmitContentReportAction;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use Domain\Users\Models\User;

final class ReportContentAction
{
    public function __construct(
        private readonly SubmitContentReportAction $submit,
    ) {}

    public function execute(int $userId, string $type, int $id, string $reason, ?string $description): ContentReport
    {
        $reporter = User::where('id', $userId)->firstOrFail();

        return $this->submit->execute(
            $reporter,
            ReportTargetType::from($type),
            (string) $id,
            ContentReportReason::from($reason),
            $description,
        );
    }
}
