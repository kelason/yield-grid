<?php

declare(strict_types=1);

namespace App\Farming\Controllers;

use App\Constants\FloodRiskConstants;
use App\Domain\Farming\Actions\AssessPlotFloodRiskAction;
use App\Domain\Farming\DTOs\FloodRiskAssessment;
use App\Domain\Farming\Enums\FloodRiskLevel;
use App\Farming\Requests\PreviewFloodRiskRequest;
use App\Farming\Resources\FloodRiskResource;
use App\Shared\Controllers\Controller;
use Carbon\CarbonImmutable;
use Domain\Farming\Models\Plot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlotFloodRiskController extends Controller
{
    public function preview(PreviewFloodRiskRequest $request, AssessPlotFloodRiskAction $assess): JsonResponse
    {
        $assessment = $assess($request->validated()['coordinates']);

        return (new FloodRiskResource($assessment))->response();
    }

    public function show(Request $request, Plot $plot): JsonResponse
    {
        $this->authorize('view', $plot);

        return (new FloodRiskResource($this->storedAssessment($plot)))->response();
    }

    public function refresh(Request $request, Plot $plot, AssessPlotFloodRiskAction $assess): JsonResponse
    {
        $this->authorize('update', $plot);

        $assessment = $assess->forPlot($plot);

        DB::table('plots')->where('id', $plot->id)->update([
            'flood_risk_level' => $assessment->level->value,
            'flood_within_coverage' => $assessment->withinCoverage,
            'flood_risk_assessed_at' => $assessment->assessedAt,
        ]);

        return (new FloodRiskResource($assessment))->response();
    }

    private function storedAssessment(Plot $plot): FloodRiskAssessment
    {
        $level = FloodRiskLevel::tryFrom((string) ($plot->flood_risk_level ?? '')) ?? FloodRiskLevel::UNKNOWN;
        $withinCoverage = (bool) ($plot->flood_within_coverage ?? false);
        $assessedAt = $plot->flood_risk_assessed_at;

        return new FloodRiskAssessment(
            level: $level,
            advice: FloodRiskConstants::adviceFor($level, $withinCoverage),
            withinCoverage: $withinCoverage,
            assessedAt: $assessedAt instanceof CarbonImmutable ? $assessedAt : null,
        );
    }
}
