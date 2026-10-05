<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;

final class CreateClaimAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(InsuranceEnrollment $enrollment, array $validated): InsuranceClaim
    {
        return InsuranceClaim::create([
            'enrollment_id' => $enrollment->id,
            'loss_date' => $validated['loss_date'],
            'cause' => $validated['cause'],
            'description' => $validated['description'] ?? null,
        ]);
    }
}
