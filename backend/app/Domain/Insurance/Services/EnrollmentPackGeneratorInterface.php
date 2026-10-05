<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Services;

use App\Domain\Insurance\Models\InsuranceEnrollment;

interface EnrollmentPackGeneratorInterface
{
    /**
     * Render the enrollment pack PDF, store it on the local disk, and return its path.
     */
    public function generateEnrollmentPack(InsuranceEnrollment $enrollment): string;
}
