<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Events;

use App\Domain\Insurance\Models\InsuranceEnrollment;
use Illuminate\Foundation\Events\Dispatchable;

final class EnrollmentPackGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly InsuranceEnrollment $enrollment,
    ) {}
}
