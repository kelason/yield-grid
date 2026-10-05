<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use LogicException;

final class AdvanceEnrollmentStatusAction
{
    /**
     * @return array<string, list<EnrollmentStatus>>
     */
    private function transitions(): array
    {
        return [
            EnrollmentStatus::DRAFT->value => [
                EnrollmentStatus::DOCUMENTS_READY,
                EnrollmentStatus::CANCELLED,
            ],
            EnrollmentStatus::DOCUMENTS_READY->value => [
                EnrollmentStatus::SUBMITTED_TO_MAO,
                EnrollmentStatus::DRAFT,
                EnrollmentStatus::CANCELLED,
            ],
            EnrollmentStatus::SUBMITTED_TO_MAO->value => [
                EnrollmentStatus::ACTIVE,
                EnrollmentStatus::REJECTED,
                EnrollmentStatus::CANCELLED,
            ],
            EnrollmentStatus::ACTIVE->value => [
                EnrollmentStatus::EXPIRED,
            ],
            EnrollmentStatus::EXPIRED->value => [],
            EnrollmentStatus::REJECTED->value => [],
            EnrollmentStatus::CANCELLED->value => [],
        ];
    }

    public function execute(InsuranceEnrollment $enrollment, EnrollmentStatus $to): InsuranceEnrollment
    {
        $allowed = $this->transitions()[$enrollment->status->value] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new LogicException(
                "Cannot move enrollment from {$enrollment->status->value} to {$to->value}."
            );
        }

        if ($to === EnrollmentStatus::DOCUMENTS_READY && $enrollment->pack_status !== PackStatus::READY) {
            throw new LogicException('Download the enrollment pack before marking documents ready.');
        }

        $enrollment->update(['status' => $to]);

        return $enrollment->refresh();
    }
}
