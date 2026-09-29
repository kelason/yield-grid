<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Support\Facades\DB;
use LogicException;

final class WithdrawDemandOfferAction
{
    public function execute(CropDemandOffer $offer): CropDemandOffer
    {
        return DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if ($lockedOffer->status !== DemandOfferStatus::PENDING) {
                throw new LogicException('Only pending offers can be withdrawn.');
            }

            $lockedOffer->update(['status' => DemandOfferStatus::WITHDRAWN]);

            return $lockedOffer->fresh() ?? $lockedOffer;
        });
    }
}
