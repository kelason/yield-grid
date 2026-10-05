<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Actions;

use App\Domain\Insurance\Enums\ClaimStatus;
use App\Domain\Insurance\Models\InsuranceClaim;
use LogicException;

final class AdvanceClaimStatusAction
{
    /**
     * @return array<string, list<ClaimStatus>>
     */
    private function transitions(): array
    {
        return [
            ClaimStatus::DRAFT->value => [
                ClaimStatus::NOTICE_OF_LOSS_FILED,
            ],
            ClaimStatus::NOTICE_OF_LOSS_FILED->value => [
                ClaimStatus::FIELD_INSPECTION,
                ClaimStatus::REJECTED,
            ],
            ClaimStatus::FIELD_INSPECTION->value => [
                ClaimStatus::ADJUSTMENT,
                ClaimStatus::REJECTED,
            ],
            ClaimStatus::ADJUSTMENT->value => [
                ClaimStatus::APPROVED,
                ClaimStatus::REJECTED,
            ],
            ClaimStatus::APPROVED->value => [
                ClaimStatus::PAID,
            ],
            ClaimStatus::PAID->value => [],
            ClaimStatus::REJECTED->value => [],
        ];
    }

    public function execute(InsuranceClaim $claim, ClaimStatus $to, ?float $paidAmountPhp = null): InsuranceClaim
    {
        $allowed = $this->transitions()[$claim->status->value] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new LogicException(
                "Cannot move claim from {$claim->status->value} to {$to->value}."
            );
        }

        $attributes = ['status' => $to];

        if ($to === ClaimStatus::NOTICE_OF_LOSS_FILED && $claim->notice_of_loss_filed_at === null) {
            $attributes['notice_of_loss_filed_at'] = now();
        }

        if ($to === ClaimStatus::PAID) {
            $attributes['paid_at'] = now();
            $attributes['paid_amount_php'] = $paidAmountPhp;
        }

        $claim->update($attributes);

        return $claim->refresh();
    }
}
