<?php

declare(strict_types=1);

namespace App\Domain\Insurance\DTOs;

use App\Domain\Insurance\Models\InsuranceClaim;
use App\Domain\Insurance\Models\InsuranceEnrollment;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class ReminderCandidatesData
{
    /**
     * @param  array<int, string>  $regions  Farmer id to PSGC region code.
     * @param  Collection<int|string, EloquentCollection<int, InsuranceClaim>>  $noticeClaims
     * @param  Collection<int|string, EloquentCollection<int, InsuranceEnrollment>>  $renewals
     * @param  Collection<int|string, EloquentCollection<int, InsuranceClaim>>  $idleClaims
     */
    public function __construct(
        public readonly array $regions,
        public readonly Collection $noticeClaims,
        public readonly Collection $renewals,
        public readonly Collection $idleClaims,
    ) {}
}
