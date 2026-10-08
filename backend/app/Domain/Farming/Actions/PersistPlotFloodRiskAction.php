<?php

declare(strict_types=1);

namespace App\Domain\Farming\Actions;

use App\Domain\Farming\DTOs\FloodRiskAssessment;
use Illuminate\Support\Facades\DB;

final class PersistPlotFloodRiskAction
{
    public function __invoke(int $plotId, FloodRiskAssessment $assessment): void
    {
        DB::table('plots')->where('id', $plotId)->update([
            'flood_risk_level' => $assessment->level->value,
            'flood_within_coverage' => $assessment->withinCoverage,
            'flood_risk_assessed_at' => $assessment->assessedAt,
        ]);
    }
}
