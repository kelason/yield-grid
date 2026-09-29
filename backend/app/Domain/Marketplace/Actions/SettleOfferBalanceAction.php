<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Domain\Marketplace\Models\Purchase;
use Illuminate\Support\Facades\DB;
use LogicException;

final class SettleOfferBalanceAction
{
    public function execute(CropDemandOffer $offer): CropDemandOffer
    {
        $settled = DB::transaction(function () use ($offer): CropDemandOffer {
            $lockedOffer = CropDemandOffer::where('id', $offer->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedOffer->status, [DemandOfferStatus::DELIVERED, DemandOfferStatus::COMPLETED], true)) {
                throw new LogicException('Balance can only be settled once the offer is delivered.');
            }

            $purchase = Purchase::where('crop_demand_offer_id', $lockedOffer->id)
                ->where('payment_status', PaymentStatus::COMPLETED)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($purchase === null || ! $purchase->is_downpayment) {
                throw new LogicException('This offer has no outstanding online balance to settle.');
            }

            $total = (float) ($purchase->total_contract_amount ?? $lockedOffer->total_price);

            if ((float) $purchase->amount_paid >= $total) {
                throw new LogicException('This offer is already paid in full.');
            }

            $purchase->update(['amount_paid' => $total]);

            return $lockedOffer->fresh() ?? $lockedOffer;
        });

        return $settled;
    }
}
