<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Constants\ReportingConstants;
use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Domain\Shared\Services\ContentTargetResolver;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SubmitContentReportAction
{
    private const string UNIQUE_VIOLATION_SQLSTATE = '23505';

    public function __construct(
        private readonly ContentReportRepositoryInterface $reports,
        private readonly ContentTargetResolver $targets,
    ) {}

    public function execute(
        User $reporter,
        ReportTargetType $type,
        string $targetId,
        ContentReportReason $reason,
        ?string $description,
    ): ContentReport {
        $targetId = trim($targetId);
        $description = $this->normalizeDescription($description, $reason);

        $existing = $this->reports->findByReporterTarget($reporter->id, $type, $targetId);

        if ($existing instanceof ContentReport) {
            return $existing;
        }

        $target = $this->targets->find($type, $targetId);
        $this->targets->assertReportable($reporter, $target);

        return $this->insertReport($reporter, $type, $targetId, $reason, $description, $target);
    }

    private function insertReport(
        User $reporter,
        ReportTargetType $type,
        string $targetId,
        ContentReportReason $reason,
        ?string $description,
        Model $target,
    ): ContentReport {
        // Safe to call inside or outside a transaction: the savepoint keeps a
        // unique violation from aborting an enclosing PostgreSQL transaction,
        // so the recovery read below always runs on a usable connection.
        try {
            return DB::transaction(fn (): ContentReport => $this->reports->create([
                'user_id' => $reporter->id,
                'reportable_type' => $type->value,
                'reportable_id' => (int) $targetId,
                'reason' => $reason->value,
                'description' => $description,
                'status' => ContentReportStatus::OPEN->value,
                'target_snapshot' => $this->targets->snapshot($target),
            ]));
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            return $this->recoverDuplicate($reporter->id, $type, $targetId, $e);
        }
    }

    private function recoverDuplicate(
        int $reporterId,
        ReportTargetType $type,
        string $targetId,
        QueryException $conflict,
    ): ContentReport {
        // The insert above runs behind a savepoint, so this recovery read
        // never runs inside an aborted PostgreSQL transaction, whether or
        // not the caller holds an enclosing transaction.
        $recovered = $this->reports->findByReporterTarget($reporterId, $type, $targetId);

        if (! $recovered instanceof ContentReport) {
            throw $conflict;
        }

        return $recovered;
    }

    private function normalizeDescription(?string $description, ContentReportReason $reason): ?string
    {
        $description = $description === null ? null : trim($description);

        if ($description === '') {
            $description = null;
        }

        if ($reason === ContentReportReason::OTHER && $description === null) {
            throw new InvalidArgumentException(ReportingConstants::OTHER_REASON_DESCRIPTION_MESSAGE);
        }

        if ($description !== null && mb_strlen($description) > ReportingConstants::DESCRIPTION_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'The description must not exceed '.ReportingConstants::DESCRIPTION_MAX_LENGTH.' characters.'
            );
        }

        return $description;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return (string) $e->getCode() === self::UNIQUE_VIOLATION_SQLSTATE;
    }
}
