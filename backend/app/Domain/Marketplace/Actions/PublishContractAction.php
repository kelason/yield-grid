<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\DTOs\PublishContractDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

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
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recommendation' => 'This recommendation has already been published.'
            ]);
        }

        if ($recommendation->status === RecommendationStatus::REJECTED || $recommendation->status === RecommendationStatus::FAILED) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recommendation' => 'Rejected or failed recommendations cannot be published.'
            ]);
        }

        return DB::transaction(function () use ($dto, $recommendation) {
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
        });
    }
}
