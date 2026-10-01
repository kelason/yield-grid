<?php

declare(strict_types=1);

namespace App\CreditScoring\Resources;

use App\Domain\CreditScoring\Actions\GenerateImprovementTipsAction;
use App\Domain\CreditScoring\DTOs\CreditScoreData;
use App\Domain\CreditScoring\DTOs\ScoreBreakdownData;
use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CreditScoreSnapshot|CreditScoreData
 */
final class CreditScoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof CreditScoreData) {
            return $this->fromData($this->resource);
        }

        return $this->fromSnapshot($this->resource);
    }

    /**
     * @return array<string, mixed>
     */
    private function fromData(CreditScoreData $data): array
    {
        return [
            'overall_score' => $data->overallScore,
            'tier' => $data->tier->value,
            'tier_label' => $data->tier->label(),
            'tier_description' => $data->tier->description(),
            'dimension_scores' => $data->breakdown->toArray(),
            'raw_metrics' => $data->rawMetrics,
            'improvement_tips' => $data->improvementTips,
            'report_status' => 'none',
            'is_report_ready' => false,
            'is_report_expired' => true,
            'report_generated_at' => null,
            'report_expires_at' => null,
            'snapshot_date' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromSnapshot(CreditScoreSnapshot $snapshot): array
    {
        return [
            'overall_score' => $snapshot->overall_score,
            'tier' => $snapshot->tier->value,
            'tier_label' => $snapshot->tier->label(),
            'tier_description' => $snapshot->tier->description(),
            'dimension_scores' => $snapshot->dimension_scores,
            'raw_metrics' => $snapshot->raw_metrics,
            'improvement_tips' => app(GenerateImprovementTipsAction::class)->execute(
                ScoreBreakdownData::fromArray($snapshot->dimension_scores ?? []),
                $snapshot->raw_metrics ?? [],
            ),
            'report_status' => $snapshot->report_status->value,
            'report_token' => $this->when($snapshot->report_token !== null, $snapshot->report_token),
            'is_report_ready' => $snapshot->is_report_ready,
            'is_report_expired' => $snapshot->is_report_expired,
            'report_generated_at' => $snapshot->report_generated_at?->toIso8601String(),
            'report_expires_at' => $snapshot->report_expires_at?->toIso8601String(),
            'snapshot_date' => $snapshot->created_at?->toIso8601String(),
        ];
    }
}
