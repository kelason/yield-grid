<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Enums\PackStatus;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use App\Jobs\GenerateEnrollmentPackJob;
use Illuminate\Support\Str;

final class GenerateEnrollmentPackAction
{
    public function execute(InsuranceEnrollment $enrollment): void
    {
        $enrollment->update([
            'pack_status' => PackStatus::GENERATING,
            'pack_token' => Str::random(InsuranceConstants::PACK_TOKEN_LENGTH),
            'pack_expires_at' => now()->addDays(InsuranceConstants::PACK_EXPIRY_DAYS),
        ]);

        GenerateEnrollmentPackJob::dispatch($enrollment->id);
    }
}
