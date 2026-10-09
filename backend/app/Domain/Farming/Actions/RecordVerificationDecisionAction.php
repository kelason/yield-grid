<?php

declare(strict_types=1);

namespace Domain\Farming\Actions;

use Domain\Farming\Enums\VerificationDecision;
use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use LogicException;

class RecordVerificationDecisionAction
{
    public function execute(Farm|Plot $target, VerificationDecision $decision, ?VerificationMethod $method, ?string $note, User $admin): Farm|Plot
    {
        $this->guardTransition($target->verification_status, $decision);

        match ($decision) {
            VerificationDecision::VERIFY => $target->fill([
                'verification_status' => VerificationStatus::VERIFIED,
                'verification_method' => $method,
                'verification_note' => $note,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]),
            VerificationDecision::REJECT,
            VerificationDecision::REVOKE => $target->fill([
                'verification_status' => VerificationStatus::REJECTED,
                'verification_method' => null,
                'verification_note' => $note,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]),
            VerificationDecision::REOPEN => $target->fill([
                'verification_status' => VerificationStatus::PENDING,
                'verification_method' => null,
                'verification_note' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]),
        };

        $target->save();

        return $target->refresh();
    }

    /**
     * @throws LogicException
     */
    private function guardTransition(?VerificationStatus $current, VerificationDecision $decision): void
    {
        $allowed = match ($decision) {
            VerificationDecision::VERIFY => [VerificationStatus::PENDING, VerificationStatus::REJECTED],
            VerificationDecision::REJECT => [VerificationStatus::PENDING],
            VerificationDecision::REVOKE => [VerificationStatus::VERIFIED],
            VerificationDecision::REOPEN => [VerificationStatus::REJECTED],
        };

        if (! in_array($current, $allowed, true)) {
            throw new LogicException("Cannot {$decision->value} a {$current?->value} record.");
        }
    }
}
