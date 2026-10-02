<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Events;

use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use Illuminate\Foundation\Events\Dispatchable;

final class ScoreReportGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly CreditScoreSnapshot $snapshot,
    ) {}
}
