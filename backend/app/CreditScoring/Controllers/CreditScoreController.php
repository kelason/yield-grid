<?php

declare(strict_types=1);

namespace App\CreditScoring\Controllers;

use App\Constants\CreditScoringConstants;
use App\Constants\HttpCode;
use App\CreditScoring\Requests\GenerateReportRequest;
use App\CreditScoring\Resources\CreditScoreResource;
use App\CreditScoring\Resources\ScoreSnapshotResource;
use App\Domain\CreditScoring\Actions\CalculateCreditScoreAction;
use App\Domain\CreditScoring\Actions\GenerateScoreReportAction;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CreditScoreController extends Controller
{
    public function show(): CreditScoreResource
    {
        $this->authorize('viewOwn', CreditScoreSnapshot::class);

        $snapshot = CreditScoreSnapshot::byUser((int) auth()->id())->latestSnapshot()->first();

        if ($snapshot === null) {
            $score = app(CalculateCreditScoreAction::class)->execute(auth()->user());

            return new CreditScoreResource($score);
        }

        return new CreditScoreResource($snapshot);
    }

    public function history(): AnonymousResourceCollection
    {
        $this->authorize('viewOwn', CreditScoreSnapshot::class);

        $snapshots = CreditScoreSnapshot::byUser((int) auth()->id())
            ->latestSnapshot()
            ->limit(CreditScoringConstants::SCORE_HISTORY_MAX_RESULTS)
            ->get();

        return ScoreSnapshotResource::collection($snapshots);
    }

    public function generateReport(GenerateReportRequest $request, GenerateScoreReportAction $action): JsonResponse
    {
        $this->authorize('generateReport', CreditScoreSnapshot::class);

        $snapshot = CreditScoreSnapshot::byUser((int) auth()->id())->latestSnapshot()->firstOrFail();

        $action->execute($snapshot);

        return response()->json(
            ['message' => 'Report generation started.'],
            HttpCode::ACCEPTED
        );
    }

    public function downloadReport(string $token): StreamedResponse|JsonResponse
    {
        $snapshot = CreditScoreSnapshot::where('report_token', $token)->firstOrFail();

        $this->authorize('download', $snapshot);

        if ($snapshot->is_report_expired || $snapshot->report_path === null) {
            return response()->json(['message' => 'Report has expired.'], HttpCode::GONE);
        }

        return Storage::disk('local')->download($snapshot->report_path, 'YieldGrid-Trust-Score-Report.pdf');
    }

    public function verifyReport(string $token): JsonResponse
    {
        $snapshot = CreditScoreSnapshot::where('report_token', $token)->first();

        if ($snapshot === null || $snapshot->is_report_expired) {
            return response()->json(['valid' => false], HttpCode::NOT_FOUND);
        }

        return response()->json([
            'valid' => true,
            'farmer_name' => $snapshot->user->name,
            'score' => $snapshot->overall_score,
            'tier' => $snapshot->tier->label(),
            'generated_at' => $snapshot->report_generated_at?->toIso8601String(),
        ]);
    }
}
