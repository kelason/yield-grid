<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Repositories\ContentReportRepositoryInterface;
use App\Domain\Shared\Services\ContentTargetResolver;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Support\Carbon;

/**
 * Database-wide operational counts for the admin overview. Every figure is
 * an aggregate query; no rows are loaded and nothing is written.
 *
 * @phpstan-type VisibilityBucket array{total: int, visible: int, hidden: int}
 * @phpstan-type OverviewPayload array{
 *   users: array{members_total: int, members_suspended: int},
 *   content: array{thread: VisibilityBucket, reply: VisibilityBucket, contract: VisibilityBucket, listing: VisibilityBucket, demand: VisibilityBucket},
 *   inquiries: array{unread: int, read: int, replied: int, closed: int, failed_replies: int},
 *   reports: array{open: int, reviewing: int, resolved: int, dismissed: int},
 *   issues: array{open: int, in_progress: int, resolved: int, closed: int},
 *   generated_at: string,
 * }
 */
final class GetAdminOverviewAction
{
    public function __construct(
        private readonly ContentReportRepositoryInterface $reports,
        private readonly IssueTicketRepositoryInterface $issues,
        private readonly ContactMessageReplyRepositoryInterface $replies,
        private readonly ContentTargetResolver $targets,
    ) {}

    /**
     * @return array{
     *   users: array{members_total: int, members_suspended: int},
     *   content: array<string, array{total: int, visible: int, hidden: int}>,
     *   inquiries: array{unread: int, read: int, replied: int, closed: int, failed_replies: int},
     *   reports: array{open: int, reviewing: int, resolved: int, dismissed: int},
     *   issues: array{open: int, in_progress: int, resolved: int, closed: int},
     *   generated_at: string,
     * }
     */
    public function execute(): array
    {
        return [
            'users' => $this->userCounts(),
            'content' => $this->contentCounts(),
            'inquiries' => $this->inquiryCounts(),
            'reports' => $this->reportCounts(),
            'issues' => $this->issueCounts(),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * @return array{members_total: int, members_suspended: int}
     */
    private function userCounts(): array
    {
        $members = User::whereIn('role', [UserRole::FARMER->value, UserRole::BUYER->value]);

        return [
            'members_total' => (clone $members)->count(),
            'members_suspended' => (clone $members)->whereNotNull('suspended_at')->count(),
        ];
    }

    /**
     * @return array<string, array{total: int, visible: int, hidden: int}>
     */
    private function contentCounts(): array
    {
        $counts = [];

        foreach (ReportTargetType::cases() as $type) {
            $counts[$type->value] = $this->targets->visibilityCounts($type);
        }

        return $counts;
    }

    /**
     * @return array{unread: int, read: int, replied: int, closed: int, failed_replies: int}
     */
    private function inquiryCounts(): array
    {
        $delivery = $this->replies->countsByStatus();

        return [
            'unread' => ContactMessage::where('status', ContactStatus::UNREAD->value)->count(),
            'read' => ContactMessage::where('status', ContactStatus::READ->value)->count(),
            'replied' => ContactMessage::where('status', ContactStatus::REPLIED->value)->count(),
            'closed' => ContactMessage::where('status', ContactStatus::CLOSED->value)->count(),
            'failed_replies' => $delivery[ReplyDeliveryStatus::FAILED->value] ?? 0,
        ];
    }

    /**
     * @return array{open: int, reviewing: int, resolved: int, dismissed: int}
     */
    private function reportCounts(): array
    {
        $counts = $this->reports->countsByStatus();

        return [
            'open' => $counts[ContentReportStatus::OPEN->value] ?? 0,
            'reviewing' => $counts[ContentReportStatus::REVIEWING->value] ?? 0,
            'resolved' => $counts[ContentReportStatus::RESOLVED->value] ?? 0,
            'dismissed' => $counts[ContentReportStatus::DISMISSED->value] ?? 0,
        ];
    }

    /**
     * @return array{open: int, in_progress: int, resolved: int, closed: int}
     */
    private function issueCounts(): array
    {
        $counts = $this->issues->countsByStatus();

        return [
            'open' => $counts[IssueStatus::OPEN->value] ?? 0,
            'in_progress' => $counts[IssueStatus::IN_PROGRESS->value] ?? 0,
            'resolved' => $counts[IssueStatus::RESOLVED->value] ?? 0,
            'closed' => $counts[IssueStatus::CLOSED->value] ?? 0,
        ];
    }
}
