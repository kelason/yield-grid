<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Domain\Insurance\Enums\EnrollmentStatus;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use LogicException;

final class RecordPolicyDetailsAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(InsuranceEnrollment $enrollment, array $validated): InsuranceEnrollment
    {
        if ($enrollment->status !== EnrollmentStatus::SUBMITTED_TO_MAO) {
            throw new LogicException('Policy details can only be recorded after MAO submission.');
        }

        $enrollment->update([
            'cic_number' => $validated['cic_number'],
            'coverage_amount_php' => $validated['coverage_amount_php'] ?? null,
            'enrolled_at' => $validated['enrolled_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'status' => EnrollmentStatus::ACTIVE,
        ]);

        return $enrollment->refresh();
    }
}
