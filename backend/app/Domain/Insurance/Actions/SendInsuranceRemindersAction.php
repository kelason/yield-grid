<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\InsuranceProgram;
use App\Domain\Insurance\Enums\ReminderType;
use App\Domain\Insurance\Enums\Season;
use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Domain\Insurance\Models\InsuranceReminderLog;
use App\Domain\Insurance\Models\PlantingWindow;
use App\Insurance\Mail\InsuranceReminderMail;
use Domain\Users\Enums\UserRole;
use Domain\Users\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendInsuranceRemindersAction
{
    public function __construct(
        private readonly ResolveFarmerRegionAction $regionAction,
        private readonly ResolvePlantingWindowAction $windowAction,
    ) {}

    /**
     * Evaluate every verified farmer and send due reminders. Returns the send count.
     */
    public function execute(): int
    {
        $count = 0;
        $farmers = User::where('role', UserRole::FARMER)->whereNotNull('email_verified_at')->cursor();

        foreach ($farmers as $farmer) {
            $count += $this->remindFarmer($farmer);
        }

        return $count;
    }

    private function remindFarmer(User $farmer): int
    {
        $region = $this->regionAction->execute($farmer);

        return $this->remindEnrollmentWindows($farmer, $region)
            + $this->remindNoticeDeadlines($farmer)
            + $this->remindRenewals($farmer)
            + $this->remindIdleClaims($farmer);
    }

    private function remindEnrollmentWindows(User $farmer, ?string $region): int
    {
        $sent = 0;

        foreach ($this->dueSeasons($region, Carbon::now()) as $due) {
            $key = "window:{$due['program']->value}:{$due['season']->value}:{$due['year']}";

            if ($this->sendOnce($farmer, ReminderType::ENROLLMENT_WINDOW, $key, [
                'program' => $due['program']->value,
                'season' => $due['season']->value,
                'season_year' => $due['year'],
                'window_label' => $due['label'],
            ])) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return list<array{program: InsuranceProgram, season: Season, year: int, label: string}>
     */
    private function dueSeasons(?string $region, Carbon $now): array
    {
        $due = [];

        foreach (Season::cases() as $season) {
            foreach (InsuranceProgram::cases() as $program) {
                $window = $this->windowAction->execute($region, $program, $season);
                $year = $this->dueWindowYear($window, $now);

                if ($year !== null) {
                    $due[$program->value.':'.$season->value] = [
                        'program' => $program,
                        'season' => $season,
                        'year' => $year,
                        'label' => $this->windowLabel($window),
                    ];
                }
            }
        }

        return array_values($due);
    }

    private function dueWindowYear(?PlantingWindow $window, Carbon $now): ?int
    {
        if ($window === null) {
            return null;
        }

        // Include last year: dry-season windows spill into January/February.
        foreach ([$now->year - 1, $now->year, $now->year + 1] as $year) {
            $start = Carbon::create($year, $window->window_start_month, $window->window_start_day)
                ->startOfDay();
            $endYear = $window->window_end_month < $window->window_start_month ? $year + 1 : $year;
            $end = Carbon::create($endYear, $window->window_end_month, $window->window_end_day)
                ->endOfDay();
            $leadStart = $start->copy()->subDays(InsuranceConstants::ENROLLMENT_REMINDER_LEAD_DAYS);

            if ($now->between($leadStart, $end)) {
                return $year;
            }
        }

        return null;
    }

    private function windowLabel(PlantingWindow $window): string
    {
        $start = Carbon::create(2000, $window->window_start_month, $window->window_start_day);
        $end = Carbon::create(2000, $window->window_end_month, $window->window_end_day);

        return $start->format('M j').' – '.$end->format('M j');
    }

    private function remindNoticeDeadlines(User $farmer): int
    {
        $cutoff = Carbon::now()->subDays(
            InsuranceConstants::NOTICE_OF_LOSS_DEADLINE_DAYS
            - InsuranceConstants::NOTICE_OF_LOSS_REMINDER_THRESHOLD_DAYS
        );

        $claims = InsuranceClaim::whereHas('enrollment', fn ($query) => $query->where('user_id', $farmer->id))
            ->where('status', ClaimStatus::DRAFT)
            ->whereNull('notice_of_loss_filed_at')
            ->whereDate('loss_date', '<=', $cutoff->toDateString())
            ->get();

        $sent = 0;

        foreach ($claims as $claim) {
            $deadline = $claim->loss_date->copy()->addDays(InsuranceConstants::NOTICE_OF_LOSS_DEADLINE_DAYS);

            if ($this->sendOnce($farmer, ReminderType::NOTICE_OF_LOSS_DEADLINE, "claim:{$claim->id}", [
                'claim_id' => $claim->id,
                'enrollment_id' => $claim->enrollment_id,
                'loss_date' => $claim->loss_date->toDateString(),
                'deadline' => $deadline->toDateString(),
            ], claimId: $claim->id)) {
                $sent++;
            }
        }

        return $sent;
    }

    private function remindRenewals(User $farmer): int
    {
        $now = Carbon::now();

        $enrollments = InsuranceEnrollment::where('user_id', $farmer->id)
            ->where('status', EnrollmentStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>', $now->toDateString())
            ->whereDate('expires_at', '<=', $now->copy()->addDays(InsuranceConstants::RENEWAL_REMINDER_LEAD_DAYS)->toDateString())
            ->get();

        $sent = 0;

        foreach ($enrollments as $enrollment) {
            if ($this->sendOnce($farmer, ReminderType::RENEWAL, "enrollment:{$enrollment->id}", [
                'enrollment_id' => $enrollment->id,
                'expires_at' => $enrollment->expires_at->toDateString(),
            ], enrollmentId: $enrollment->id)) {
                $sent++;
            }
        }

        return $sent;
    }

    private function remindIdleClaims(User $farmer): int
    {
        $idleSince = Carbon::now()->subDays(InsuranceConstants::CLAIM_FOLLOWUP_AFTER_DAYS);

        $claims = InsuranceClaim::whereHas('enrollment', fn ($query) => $query->where('user_id', $farmer->id))
            ->whereIn('status', [
                ClaimStatus::NOTICE_OF_LOSS_FILED,
                ClaimStatus::FIELD_INSPECTION,
                ClaimStatus::ADJUSTMENT,
                ClaimStatus::APPROVED,
            ])
            ->where('updated_at', '<', $idleSince)
            ->get();

        $sent = 0;

        foreach ($claims as $claim) {
            if ($this->sendOnce($farmer, ReminderType::CLAIM_FOLLOWUP, "followup:claim:{$claim->id}", [
                'claim_id' => $claim->id,
                'enrollment_id' => $claim->enrollment_id,
                'status' => $claim->status->value,
            ], claimId: $claim->id)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Insert-or-ignore claims the unique key atomically, so overlapping cron
     * runs can never throw or double-send. A failed mail dispatch deletes the
     * claim so the next run retries instead of losing the reminder silently.
     *
     * @param  array<string, mixed>  $meta
     */
    private function sendOnce(
        User $farmer,
        ReminderType $type,
        string $key,
        array $meta,
        ?int $enrollmentId = null,
        ?int $claimId = null,
    ): bool {
        $claimed = DB::table('insurance_reminder_logs')->insertOrIgnore([
            'user_id' => $farmer->id,
            'type' => $type->value,
            'reference_key' => $key,
            'meta' => json_encode($meta),
            'enrollment_id' => $enrollmentId,
            'claim_id' => $claimId,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($claimed === 0) {
            return false;
        }

        try {
            Mail::to($farmer->email)->send(new InsuranceReminderMail($type, $meta));
        } catch (Throwable $e) {
            InsuranceReminderLog::where('user_id', $farmer->id)
                ->where('type', $type)
                ->where('reference_key', $key)
                ->delete();

            report($e);

            return false;
        }

        return true;
    }
}
