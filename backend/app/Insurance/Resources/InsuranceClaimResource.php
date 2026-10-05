<?php

declare(strict_types=1);

namespace App\Insurance\Resources;

use App\Constants\InsuranceConstants;
use App\Domain\Insurance\Models\InsuranceClaim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InsuranceClaim
 */
final class InsuranceClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $deadline = $this->loss_date->copy()->addDays(InsuranceConstants::NOTICE_OF_LOSS_DEADLINE_DAYS);

        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'loss_date' => $this->loss_date->toDateString(),
            'cause' => $this->cause->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'notice_of_loss_deadline' => $deadline->toDateString(),
            'notice_of_loss_overdue' => $this->notice_of_loss_filed_at === null && $deadline->isPast(),
            'notice_of_loss_filed_at' => $this->notice_of_loss_filed_at?->toIso8601String(),
            'paid_amount_php' => $this->paid_amount_php,
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
