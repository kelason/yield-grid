<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\Marketplace\DTOs\CreateHarvestListingDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\HarvestListing;
use Carbon\Carbon;

final class CreateHarvestListingAction
{
    public function execute(CreateHarvestListingDTO $dto): HarvestListing
    {
        $totalPrice = $dto->quantityKg * $dto->pricePerKg;

        if ($dto->isHarvestAvailable) {
            $expiryDate = Carbon::today()->addDays($dto->shelfLifeDays);
            $estimatedDateStr = Carbon::today()->format('Y-m-d');
        } else {
            $expiryDate = Carbon::parse($dto->estimatedHarvestDate)->subDay();
            $estimatedDateStr = $dto->estimatedHarvestDate;
        }

        return HarvestListing::create([
            'farmer_id' => $dto->farmerId,
            'title' => $dto->title,
            'description' => $dto->description,
            'crop_name' => $dto->cropName,
            'quantity_kg' => $dto->quantityKg,
            'price_per_kg' => $dto->pricePerKg,
            'total_price' => $totalPrice,
            'currency' => PaymentConstants::DEFAULT_CURRENCY,
            'estimated_harvest_date' => $estimatedDateStr,
            'expiry_date' => $expiryDate,
            'status' => ContractStatus::AVAILABLE,
            'shelf_life_days' => $dto->shelfLifeDays,
            'is_harvest_available' => $dto->isHarvestAvailable,
        ]);
    }
}
