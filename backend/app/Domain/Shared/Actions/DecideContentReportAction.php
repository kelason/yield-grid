<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Constants\ReportingConstants;
use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Models\ContentReport;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Domain\Shared\Services\ContentTargetResolver;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class DecideContentReportAction
{
    private const string SUBJECT_TYPE = 'content_report';

    public function __construct(
        private readonly ContentReportRepositoryInterface $reports,
        private readonly ContentTargetResolver $targets,
        private readonly ModerateForumContentAction $forum,
        private readonly ModerateMarketplaceContentAction $marketplace,
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    /**
     * Record an atomic admin decision on a content report.
     *
     * Holds the report row lock before any content lock (report row, then
     * content root/thread/demand, then reply/item inside the delegated
     * moderation Actions). The decision history is appended in the same
     * transaction, so a visibility failure leaves no report or history trace.
     */
    public function execute(
        User $actor,
        ContentReport $report,
        ContentReportStatus $status,
        ?string $outcome,
        string $note,
        int $expectedVersion,
    ): ContentReport {
        $note = trim($note);
        $this->guardNote($note);
        $this->guardOutcome($status, $outcome);

        return DB::transaction(
            fn (): ContentReport => $this->decideLocked($actor, $report->id, $status, $outcome, $note, $expectedVersion)
        );
    }

    private function decideLocked(
        User $actor,
        int $reportId,
        ContentReportStatus $status,
        ?string $outcome,
        string $note,
        int $expectedVersion,
    ): ContentReport {
        $locked = $this->reports->findLockedById($reportId);

        $this->guardVersion($locked, $expectedVersion);
        $this->guardTransition($locked->status, $status);

        $target = $this->resolveTarget($locked);

        if ($status === ContentReportStatus::RESOLVED && $outcome === ReportingConstants::OUTCOME_HIDDEN) {
            $this->hideTarget($actor, $locked, $target, $note);
        }

        $this->applyDecision($actor, $locked, $status, $outcome, $note);

        return $locked->refresh();
    }

    private function applyDecision(
        User $actor,
        ContentReport $report,
        ContentReportStatus $status,
        ?string $outcome,
        string $note,
    ): void {
        $before = $this->decisionState($report);

        $report->forceFill([
            'status' => $status->value,
            'outcome' => $status === ContentReportStatus::RESOLVED ? $outcome : null,
            'resolution_note' => $note,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'version' => $report->version + 1,
        ]);
        $this->reports->save($report);

        $this->history->append(
            $actor->id,
            AdminAction::REPORT_DECIDED,
            self::SUBJECT_TYPE,
            (string) $report->id,
            $note,
            $before,
            $this->decisionState($report),
            $report->id,
        );
    }

    private function resolveTarget(ContentReport $report): ?Model
    {
        $type = ReportTargetType::tryFrom((string) $report->getRawOriginal('reportable_type'));

        if (! $type instanceof ReportTargetType) {
            return null;
        }

        try {
            return $this->targets->find($type, (string) $report->reportable_id);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    private function hideTarget(User $actor, ContentReport $report, ?Model $target, string $note): void
    {
        if (! $target instanceof Model) {
            throw new LogicException('Cannot hide a report whose target is unavailable.');
        }

        $type = ReportTargetType::from((string) $report->getRawOriginal('reportable_type'));

        if ($this->isAlreadyHidden($target)) {
            return;
        }

        $id = (string) $report->reportable_id;

        if ($type === ReportTargetType::THREAD || $type === ReportTargetType::REPLY) {
            $this->forum->execute($actor, $type, $id, true, $note);

            return;
        }

        $this->marketplace->execute($actor, $type, $id, true, $note);
    }

    /**
     * Directly hidden content — or a split whose normalized root is hidden —
     * skips another hide event. An ancestor-suppressed reply still needs its
     * own flag, so forum checks stay direct.
     */
    private function isAlreadyHidden(Model $target): bool
    {
        if ($target instanceof ForwardContract || $target instanceof HarvestListing) {
            return $target->isEffectivelyHidden();
        }

        return $target->getAttribute('hidden_at') !== null;
    }

    private function guardNote(string $note): void
    {
        if ($note === '' || mb_strlen($note) > ReportingConstants::DECISION_NOTE_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'The note must be between 1 and '.ReportingConstants::DECISION_NOTE_MAX_LENGTH.' characters.'
            );
        }
    }

    private function guardOutcome(ContentReportStatus $status, ?string $outcome): void
    {
        $allowed = [ReportingConstants::OUTCOME_HIDDEN, ReportingConstants::OUTCOME_NO_ACTION];

        if ($status === ContentReportStatus::RESOLVED && ! in_array($outcome, $allowed, true)) {
            throw new InvalidArgumentException('An outcome of hidden or no_action is required when resolving.');
        }

        if ($status !== ContentReportStatus::RESOLVED && $outcome !== null) {
            throw new InvalidArgumentException('An outcome is only allowed when resolving.');
        }
    }

    private function guardVersion(ContentReport $report, int $expectedVersion): void
    {
        if ($report->version !== $expectedVersion) {
            throw new LogicException('This report changed since you loaded it. Reload and try again.');
        }
    }

    private function guardTransition(ContentReportStatus $current, ContentReportStatus $next): void
    {
        $allowed = [
            ContentReportStatus::OPEN->value => [
                ContentReportStatus::REVIEWING,
                ContentReportStatus::RESOLVED,
                ContentReportStatus::DISMISSED,
            ],
            ContentReportStatus::REVIEWING->value => [
                ContentReportStatus::RESOLVED,
                ContentReportStatus::DISMISSED,
            ],
        ];

        if (! in_array($next, $allowed[$current->value] ?? [], true)) {
            throw new LogicException("Cannot transition report from {$current->value} to {$next->value}.");
        }
    }

    /**
     * @return array{status: string, version: int, outcome: ?string}
     */
    private function decisionState(ContentReport $report): array
    {
        $status = $report->status;

        return [
            'status' => $status instanceof ContentReportStatus ? $status->value : (string) $report->getRawOriginal('status'),
            'version' => (int) $report->version,
            'outcome' => $report->outcome,
        ];
    }
}
