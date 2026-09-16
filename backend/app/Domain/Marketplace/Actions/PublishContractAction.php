<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Actions;

use App\Constants\PaymentConstants;
use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\CropRecommendation\Repositories\CropRecommendationRepositoryInterface;
use App\Domain\Marketplace\DTOs\PublishContractDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Exceptions\ContractPublishingException;
use App\Domain\Marketplace\Exceptions\UnauthorizedContractPublishingException;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Domain\Shared\Database\TransactionManagerInterface;

final class PublishContractAction
{
    public function __construct(
        private readonly CropRecommendationRepositoryInterface $recommendationRepository,
        private readonly ForwardContractRepositoryInterface $contractRepository,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(PublishContractDTO $dto): ForwardContract
    {
        $recommendation = $this->recommendationRepository->findByIdAndFarmer($dto->recommendationId, $dto->farmerId);

        if (!$recommendation) {
            throw new UnauthorizedContractPublishingException('You do not own this recommendation or it does not exist.');
        }

        if ($recommendation->is_published) {
            throw new ContractPublishingException('This recommendation has already been published.');
        }

        if ($recommendation->status === RecommendationStatus::REJECTED || $recommendation->status === RecommendationStatus::FAILED) {
            throw new ContractPublishingException('Rejected or failed recommendations cannot be published.');
        }

        return $this->transactionManager->run(function () use ($dto, $recommendation) {
            $this->recommendationRepository->markAsPublished($recommendation);

            $totalPrice = $dto->quantityKg * $dto->pricePerKg;

            return $this->contractRepository->create([
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
