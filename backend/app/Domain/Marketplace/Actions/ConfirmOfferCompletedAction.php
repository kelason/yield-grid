<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Events\DemandOfferCompleted;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ConfirmOfferCompletedAction
{
    public function execute(CropDemandOffer $offer): CropDemandOffer
    {
        $completed = DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if ($lockedOffer->status !== DemandOfferStatus::DELIVERED) {
                throw new LogicException('Only delivered offers can be confirmed as completed.');
            }

            $lockedOffer->update([
                'status' => DemandOfferStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            $demand = CropDemand::where('id', $lockedOffer->crop_demand_id)->lockForUpdate()->firstOrFail();

            $hasOutstanding = CropDemandOffer::where('crop_demand_id', $demand->id)
                ->whereIn('status', [
                    DemandOfferStatus::ACCEPTED,
                    DemandOfferStatus::PARTIALLY_PAID,
                    DemandOfferStatus::PAID,
                    DemandOfferStatus::DELIVERED,
                ])
                ->exists();

            if (! $hasOutstanding && (float) $demand->remaining_quantity_kg <= 0) {
                $demand->update(['status' => DemandStatus::FULFILLED]);
            }

            return $lockedOffer->fresh() ?? $lockedOffer;
        });

        try {
            broadcast(new DemandOfferCompleted($completed->load('demand')))->toOthers();
        } catch (\Throwable) {
        }

        return $completed;
    }
}
