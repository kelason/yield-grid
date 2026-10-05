<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\DTOs\ReminderCandidatesData;
use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class LoadReminderCandidatesAction
{
    public function __construct(
        private readonly ResolveFarmerRegionAction $regionAction,
    ) {}

    /**
     * Preload every reminder candidate in bulk, grouped by farmer id.
     *
     * @param  list<int>  $farmerIds
     */
    public function execute(array $farmerIds, Carbon $now): ReminderCandidatesData
    {
        return new ReminderCandidatesData(
            $this->regionAction->regionsForUsers($farmerIds),
            $this->dueNoticeClaims($farmerIds, $now),
            $this->dueRenewals($farmerIds, $now),
            $this->dueIdleClaims($farmerIds, $now),
        );
    }

    /**
     * Draft claims nearing the notice-of-loss deadline, grouped by farmer id.
     *
     * @param  list<int>  $farmerIds
     * @return Collection<int|string, EloquentCollection<int, InsuranceClaim>>
     */
    private function dueNoticeClaims(array $farmerIds, Carbon $now): Collection
    {
        $cutoff = $now->copy()->subDays(
            InsuranceConstants::NOTICE_OF_LOSS_DEADLINE_DAYS
            - InsuranceConstants::NOTICE_OF_LOSS_REMINDER_THRESHOLD_DAYS
        );

        return InsuranceClaim::with('enrollment:id,user_id')
            ->whereHas('enrollment', fn ($query) => $query->whereIn('user_id', $farmerIds))
            ->where('status', ClaimStatus::DRAFT)
            ->whereNull('notice_of_loss_filed_at')
            ->whereDate('loss_date', '<=', $cutoff->toDateString())
            ->get()
            ->groupBy(fn ($claim) => $claim->enrollment->user_id);
    }

    /**
     * Active enrollments expiring soon, grouped by farmer id.
     *
     * @param  list<int>  $farmerIds
     * @return Collection<int|string, EloquentCollection<int, InsuranceEnrollment>>
     */
    private function dueRenewals(array $farmerIds, Carbon $now): Collection
    {
        return InsuranceEnrollment::whereIn('user_id', $farmerIds)
            ->where('status', EnrollmentStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>', $now->toDateString())
            ->whereDate('expires_at', '<=', $now->copy()->addDays(InsuranceConstants::RENEWAL_REMINDER_LEAD_DAYS)->toDateString())
            ->get()
            ->groupBy('user_id');
    }

    /**
     * Idle in-progress claims, grouped by farmer id.
     *
     * @param  list<int>  $farmerIds
     * @return Collection<int|string, EloquentCollection<int, InsuranceClaim>>
     */
    private function dueIdleClaims(array $farmerIds, Carbon $now): Collection
    {
        $idleSince = $now->copy()->subDays(InsuranceConstants::CLAIM_FOLLOWUP_AFTER_DAYS);

        return InsuranceClaim::with('enrollment:id,user_id')
            ->whereHas('enrollment', fn ($query) => $query->whereIn('user_id', $farmerIds))
            ->whereIn('status', [
                ClaimStatus::NOTICE_OF_LOSS_FILED,
                ClaimStatus::FIELD_INSPECTION,
                ClaimStatus::ADJUSTMENT,
                ClaimStatus::APPROVED,
            ])
            ->where('updated_at', '<', $idleSince)
            ->get()
            ->groupBy(fn ($claim) => $claim->enrollment->user_id);
    }
}
