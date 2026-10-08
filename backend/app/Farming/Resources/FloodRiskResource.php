<?php

declare(strict_types=1);

namespace App\Farming\Resources;

use App\Constants\FloodRiskConstants;
use App\Domain\Farming\DTOs\FloodRiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloodRiskResource extends JsonResource
{
    public function __construct(private readonly FloodRiskAssessment $assessment)
    {
        parent::__construct($assessment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'level' => $this->assessment->level->value,
            'label' => FloodRiskConstants::labelFor($this->assessment->level),
            'legend_token' => $this->assessment->level->value,
            'advice' => $this->assessment->advice,
            'within_coverage' => $this->assessment->withinCoverage,
            'assessed_at' => $this->assessment->assessedAt?->toIso8601String(),
        ];
    }
}
