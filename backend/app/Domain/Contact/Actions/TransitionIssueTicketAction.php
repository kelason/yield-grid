<?php

declare(strict_types=1);

namespace App\Domain\Contact\Actions;

use App\Constants\IssueConstants;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use App\Domain\Shared\Enums\AdminAction;
use App\Domain\Shared\Repositories\AdminActionLogRepositoryInterface;
use Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class TransitionIssueTicketAction
{
    private const string SUBJECT_TYPE = 'issue_ticket';

    private const int HISTORY_REASON_MAX_LENGTH = 500;

    public function __construct(
        private readonly IssueTicketRepositoryInterface $issues,
        private readonly AdminActionLogRepositoryInterface $history,
    ) {}

    public function execute(
        User $actor,
        IssueTicket $issue,
        IssueStatus $status,
        ?string $resolution,
        int $expectedVersion,
    ): IssueTicket {
        $resolution = $this->normalizeResolution($resolution);
        $this->guardResolution($status, $resolution);

        return DB::transaction(
            fn (): IssueTicket => $this->transitionLocked($actor, $issue->id, $status, $resolution, $expectedVersion)
        );
    }

    private function transitionLocked(
        User $actor,
        int $issueId,
        IssueStatus $status,
        ?string $resolution,
        int $expectedVersion,
    ): IssueTicket {
        $locked = $this->issues->findLockedById($issueId);

        $this->guardVersion($locked, $expectedVersion);
        $this->guardTransition($locked->status, $status);
        $this->applyTransition($actor, $locked, $status, $resolution);

        return $locked->refresh();
    }

    private function applyTransition(
        User $actor,
        IssueTicket $issue,
        IssueStatus $status,
        ?string $resolution,
    ): void {
        $before = $this->transitionState($issue);
        $closing = $status === IssueStatus::RESOLVED || $status === IssueStatus::CLOSED;

        $issue->forceFill([
            'status' => $status->value,
            'resolution' => $closing ? $resolution : null,
            'resolved_by' => $closing ? $actor->id : null,
            'resolved_at' => $closing ? now() : null,
            'version' => $issue->version + 1,
        ]);
        $this->issues->save($issue);

        $this->history->append(
            $actor->id,
            AdminAction::ISSUE_TRANSITIONED,
            self::SUBJECT_TYPE,
            (string) $issue->id,
            $this->historyReason($before['status'], $status, $resolution),
            $before,
            $this->transitionState($issue),
        );
    }

    private function normalizeResolution(?string $resolution): ?string
    {
        if ($resolution === null) {
            return null;
        }

        $resolution = trim($resolution);

        return $resolution === '' ? null : $resolution;
    }

    private function guardResolution(IssueStatus $status, ?string $resolution): void
    {
        $closing = $status === IssueStatus::RESOLVED || $status === IssueStatus::CLOSED;

        if ($closing && $resolution === null) {
            throw new InvalidArgumentException('A resolution is required when resolving or closing.');
        }

        if ($closing && mb_strlen((string) $resolution) > IssueConstants::RESOLUTION_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'The resolution must not exceed '.IssueConstants::RESOLUTION_MAX_LENGTH.' characters.'
            );
        }

        if (! $closing && $resolution !== null) {
            throw new InvalidArgumentException('A resolution is only allowed when resolving or closing.');
        }
    }

    private function guardVersion(IssueTicket $issue, int $expectedVersion): void
    {
        if ($issue->version !== $expectedVersion) {
            throw new LogicException('This ticket changed since you loaded it. Reload and try again.');
        }
    }

    private function guardTransition(IssueStatus $current, IssueStatus $next): void
    {
        $allowed = [
            IssueStatus::OPEN->value => [IssueStatus::IN_PROGRESS, IssueStatus::RESOLVED, IssueStatus::CLOSED],
            IssueStatus::IN_PROGRESS->value => [IssueStatus::RESOLVED, IssueStatus::CLOSED],
            IssueStatus::RESOLVED->value => [IssueStatus::IN_PROGRESS, IssueStatus::CLOSED],
            IssueStatus::CLOSED->value => [IssueStatus::IN_PROGRESS],
        ];

        if (! in_array($next, $allowed[$current->value] ?? [], true)) {
            throw new LogicException("Cannot transition ticket from {$current->value} to {$next->value}.");
        }
    }

    private function historyReason(string $before, IssueStatus $status, ?string $resolution): string
    {
        if ($resolution !== null) {
            return mb_substr($resolution, 0, self::HISTORY_REASON_MAX_LENGTH);
        }

        return "Transitioned from {$before} to {$status->value}.";
    }

    /**
     * @return array{status: string, version: int}
     */
    private function transitionState(IssueTicket $issue): array
    {
        $status = $issue->status;

        return [
            'status' => $status instanceof IssueStatus ? $status->value : (string) $issue->getRawOriginal('status'),
            'version' => (int) $issue->version,
        ];
    }
}
