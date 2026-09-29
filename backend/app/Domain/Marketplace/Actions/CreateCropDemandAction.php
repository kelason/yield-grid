<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\Marketplace\DTOs\PublishDemandDTO;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use Domain\Users\Models\UserAddress;
use Illuminate\Support\Facades\DB;

final class CreateCropDemandAction
{
    public function execute(PublishDemandDTO $dto): CropDemand
    {
        return DB::transaction(function () use ($dto): CropDemand {
            $deliveryAddress = UserAddress::where('id', $dto->addressId)
                ->where('user_id', $dto->buyerId)
                ->firstOrFail();

            return CropDemand::create([
                'buyer_id' => $dto->buyerId,
                'address_id' => $deliveryAddress->id,
                'title' => $dto->title,
                'description' => $dto->description,
                'crop_name' => $dto->cropName,
                'quantity_kg' => $dto->quantityKg,
                'remaining_quantity_kg' => $dto->quantityKg,
                'target_price_per_kg' => $dto->targetPricePerKg,
                'total_budget' => $dto->quantityKg * $dto->targetPricePerKg,
                'currency' => PaymentConstants::DEFAULT_CURRENCY,
                'needed_by_date' => $dto->neededByDate,
                'expiry_date' => $dto->expiryDate,
                'status' => DemandStatus::OPEN,
            ]);
        });
    }
}
