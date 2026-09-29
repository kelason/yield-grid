<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\Marketplace\DTOs\SubmitDemandOfferDTO;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Events\DemandOfferReceived;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use Illuminate\Support\Facades\DB;
use LogicException;

final class SubmitDemandOfferAction
{
    public function execute(SubmitDemandOfferDTO $dto): CropDemandOffer
    {
        $offer = DB::transaction(function () use ($dto): CropDemandOffer {
            $demand = CropDemand::where('id', $dto->demandId)->lockForUpdate()->firstOrFail();

            if ($demand->status !== DemandStatus::OPEN || $demand->is_expired) {
                throw new LogicException('This demand is no longer accepting offers.');
            }

            if ($dto->quantityKg <= 0 || $dto->quantityKg > (float) $demand->remaining_quantity_kg) {
                throw new LogicException('Offered quantity must be between 0 and the remaining demand quantity.');
            }

            $hasActive = CropDemandOffer::where('crop_demand_id', $demand->id)
                ->where('farmer_id', $dto->farmerId)
                ->blockingNewOffer()
                ->exists();

            if ($hasActive) {
                throw new LogicException('You already have an active offer on this demand.');
            }

            return CropDemandOffer::create([
                'crop_demand_id' => $demand->id,
                'farmer_id' => $dto->farmerId,
                'quantity_kg' => $dto->quantityKg,
                'price_per_kg' => $dto->pricePerKg,
                'total_price' => $dto->quantityKg * $dto->pricePerKg,
                'currency' => PaymentConstants::DEFAULT_CURRENCY,
                'message' => $dto->message,
                'status' => DemandOfferStatus::PENDING,
            ]);
        });

        try {
            broadcast(new DemandOfferReceived($offer->load('demand')))->toOthers();
        } catch (\Throwable) {
        }

        return $offer;
    }
}
