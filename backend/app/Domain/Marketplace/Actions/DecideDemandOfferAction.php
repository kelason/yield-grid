<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Events\DemandOfferAccepted;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Support\Facades\DB;
use LogicException;

final class DecideDemandOfferAction
{
    public function accept(CropDemandOffer $offer): CropDemandOffer
    {
        $accepted = DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if ($lockedOffer->status !== DemandOfferStatus::PENDING) {
                throw new LogicException('Only pending offers can be accepted.');
            }

            $demand = CropDemand::where('id', $lockedOffer->crop_demand_id)->lockForUpdate()->firstOrFail();

            if ($demand->isHidden()) {
                throw new LogicException('This demand is no longer open for acceptance.');
            }

            if ($demand->status !== DemandStatus::OPEN || $demand->is_expired) {
                throw new LogicException('This demand is no longer open for acceptance.');
            }

            if ((float) $lockedOffer->quantity_kg > (float) $demand->remaining_quantity_kg) {
                throw new LogicException('Insufficient remaining quantity for this offer.');
            }

            $remaining = (float) $demand->remaining_quantity_kg - (float) $lockedOffer->quantity_kg;

            $demand->update([
                'remaining_quantity_kg' => $remaining,
                'status' => $remaining <= 0 ? DemandStatus::FULLY_ALLOCATED : DemandStatus::OPEN,
            ]);

            $lockedOffer->update([
                'status' => DemandOfferStatus::ACCEPTED,
                'accepted_at' => now(),
            ]);

            return $lockedOffer->fresh() ?? $lockedOffer;
        });

        try {
            broadcast(new DemandOfferAccepted($accepted->load('demand')))->toOthers();
        } catch (\Throwable) {
        }

        return $accepted;
    }

    public function reject(CropDemandOffer $offer): CropDemandOffer
    {
        return DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if ($lockedOffer->status !== DemandOfferStatus::PENDING) {
                throw new LogicException('Only pending offers can be rejected.');
            }

            $lockedOffer->update(['status' => DemandOfferStatus::REJECTED]);

            return $lockedOffer->fresh() ?? $lockedOffer;
        });
    }
}
