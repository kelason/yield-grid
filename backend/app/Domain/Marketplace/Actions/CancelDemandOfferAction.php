<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CancelDemandOfferAction
{
    /**
     * Cancel an offer and restore its quantity to the demand.
     *
     * Allowed for pending offers (farmer withdraw path uses WITHDRAWN instead
     * via WithdrawDemandOfferAction) and for accepted-but-unpaid offers.
     */
    public function execute(CropDemandOffer $offer): CropDemandOffer
    {
        return DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if ($lockedOffer->status === DemandOfferStatus::PENDING) {
                $lockedOffer->update(['status' => DemandOfferStatus::CANCELLED]);

                return $lockedOffer->fresh() ?? $lockedOffer;
            }

            if ($lockedOffer->status !== DemandOfferStatus::ACCEPTED) {
                throw new LogicException('Only pending or accepted unpaid offers can be cancelled.');
            }

            $purchase = $lockedOffer->purchase;
            if ($purchase !== null && in_array($purchase->payment_status, [PaymentStatus::PENDING, PaymentStatus::COMPLETED], true)) {
                throw new LogicException('This offer has an active payment and cannot be cancelled.');
            }

            $demand = CropDemand::where('id', $lockedOffer->crop_demand_id)->lockForUpdate()->firstOrFail();

            $demand->update([
                'remaining_quantity_kg' => (float) $demand->remaining_quantity_kg + (float) $lockedOffer->quantity_kg,
                'status' => $demand->status === DemandStatus::FULLY_ALLOCATED ? DemandStatus::OPEN : $demand->status,
            ]);

            $lockedOffer->update(['status' => DemandOfferStatus::CANCELLED]);

            return $lockedOffer->fresh() ?? $lockedOffer;
        });
    }
}
