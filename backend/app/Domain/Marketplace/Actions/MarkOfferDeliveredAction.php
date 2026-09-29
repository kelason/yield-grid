<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Events\DemandOfferDelivered;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Support\Facades\DB;
use LogicException;

final class MarkOfferDeliveredAction
{
    public function execute(CropDemandOffer $offer): CropDemandOffer
    {
        $delivered = DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedOffer->status, [DemandOfferStatus::PAID, DemandOfferStatus::PARTIALLY_PAID], true)) {
                throw new LogicException('Only paid offers can be marked as delivered.');
            }

            $lockedOffer->update([
                'status' => DemandOfferStatus::DELIVERED,
                'delivered_at' => now(),
            ]);

            return $lockedOffer->fresh() ?? $lockedOffer;
        });

        try {
            broadcast(new DemandOfferDelivered($delivered->load('demand')))->toOthers();
        } catch (\Throwable) {
        }

        return $delivered;
    }
}
