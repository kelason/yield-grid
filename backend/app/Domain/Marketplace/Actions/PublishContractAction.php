<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Models\CropRecommendation;
use App\Domain\Marketplace\DTOs\PublishContractDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use Illuminate\Auth\Access\AuthorizationException;

final class PublishContractAction
{
    public function execute(PublishContractDTO $dto): ForwardContract
    {
        $recommendation = CropRecommendation::with('plot.farm')->findOrFail($dto->recommendationId);

        // Validate the recommendation belongs to the farmer
        if ($recommendation->plot->farm->user_id !== $dto->farmerId) {
            throw new AuthorizationException('You do not own this recommendation.');
        }

        if ($recommendation->is_published) {
             throw new \InvalidArgumentException('This recommendation has already been published.');
        }

        // Accept the recommendation automatically when publishing
        $recommendation->update([
            'status' => RecommendationStatus::ACCEPTED,
            'is_published' => true,
        ]);

        $totalPrice = $dto->quantityKg * $dto->pricePerKg;

        return ForwardContract::create([
            'farmer_id' => $dto->farmerId,
            'crop_recommendation_id' => $dto->recommendationId,
            'title' => $dto->title,
            'description' => $dto->description,
            'crop_name' => $recommendation->crop_name,
            'quantity_kg' => $dto->quantityKg,
            'price_per_kg' => $dto->pricePerKg,
            'total_price' => $totalPrice,
            'currency' => PaymentConstants::DEFAULT_CURRENCY,
            'estimated_harvest_date' => $dto->estimatedHarvestDate,
            'expiry_date' => $dto->expiryDate,
            'status' => ContractStatus::AVAILABLE,
        ]);
    }
}
