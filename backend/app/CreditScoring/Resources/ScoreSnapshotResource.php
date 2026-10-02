<?php

declare(strict_types=1);

namespace App\CreditScoring\Resources;

use App\Domain\CreditScoring\Models\CreditScoreSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CreditScoreSnapshot
 */
final class ScoreSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'overall_score' => $this->overall_score,
            'tier' => $this->tier->value,
            'tier_label' => $this->tier->label(),
            'dimension_scores' => $this->dimension_scores,
            'report_status' => $this->report_status->value,
            'is_report_ready' => $this->is_report_ready,
            'snapshot_date' => $this->created_at->toIso8601String(),
        ];
    }
}
