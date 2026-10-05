<?php

declare(strict_types=1);

namespace App\Insurance\Resources;

use App\Domain\Insurance\Models\InsuranceEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InsuranceEnrollment
 */
final class InsuranceEnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plot_id' => $this->plot_id,
            'program' => $this->program->value,
            'season' => $this->season->value,
            'season_year' => $this->season_year,
            'status' => $this->status->value,
            'cic_number' => $this->cic_number,
            'coverage_amount_php' => $this->coverage_amount_php,
            'enrolled_at' => $this->enrolled_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'notes' => $this->notes,
            'pack_status' => $this->pack_status->value,
            'pack_generated_at' => $this->pack_generated_at?->toIso8601String(),
        ];
    }
}
