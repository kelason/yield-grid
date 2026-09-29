<?php

declare(strict_types=1);

namespace App\Domain\Chat\Actions;

use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

final class AssertTransactionExistsAction
{
    /**
     * Check whether two users share a live transaction: a purchase where one
     * side is the buyer and the other side farms the purchased contract,
     * listing, or demand offer — or a buyer-accepted demand offer between
     * them (chat unlocks after acceptance, before payment). Failed payments
     * and non-accepted offers do not count.
     */
    public function execute(int $userOneId, int $userTwoId): bool
    {
        $hasPurchase = Purchase::where('payment_status', '!=', PaymentStatus::FAILED->value)
            ->where(function (Builder $query) use ($userOneId, $userTwoId): void {
                $query
                    ->where(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('buyer_id', $userOneId)
                            ->whereHas('contract', fn (Builder $qq) => $qq->where('farmer_id', $userTwoId));
                    })
                    ->orWhere(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('buyer_id', $userOneId)
                            ->whereHas('harvestListing', fn (Builder $qq) => $qq->where('farmer_id', $userTwoId));
                    })
                    ->orWhere(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('buyer_id', $userTwoId)
                            ->whereHas('contract', fn (Builder $qq) => $qq->where('farmer_id', $userOneId));
                    })
                    ->orWhere(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('buyer_id', $userTwoId)
                            ->whereHas('harvestListing', fn (Builder $qq) => $qq->where('farmer_id', $userOneId));
                    })
                    ->orWhere(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('buyer_id', $userOneId)
                            ->whereHas('demandOffer', fn (Builder $qq) => $qq->where('farmer_id', $userTwoId));
                    })
                    ->orWhere(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('buyer_id', $userTwoId)
                            ->whereHas('demandOffer', fn (Builder $qq) => $qq->where('farmer_id', $userOneId));
                    });
            })
            ->exists();

        if ($hasPurchase) {
            return true;
        }

        return CropDemandOffer::chatEligible()
            ->where(function (Builder $query) use ($userOneId, $userTwoId): void {
                $query
                    ->where(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('farmer_id', $userOneId)
                            ->whereHas('demand', fn (Builder $qq) => $qq->where('buyer_id', $userTwoId));
                    })
                    ->orWhere(function (Builder $q) use ($userOneId, $userTwoId): void {
                        $q->where('farmer_id', $userTwoId)
                            ->whereHas('demand', fn (Builder $qq) => $qq->where('buyer_id', $userOneId));
                    });
            })
            ->exists();
    }
}
