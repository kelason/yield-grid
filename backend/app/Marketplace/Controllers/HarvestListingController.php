<?php

declare(strict_types=1);

namespace App\Marketplace\Controllers;

use App\Constants\HttpCode;
use App\Domain\Marketplace\Actions\CreateHarvestListingAction;
use App\Domain\Marketplace\DTOs\CreateHarvestListingDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Marketplace\Requests\StoreHarvestListingRequest;
use App\Marketplace\Resources\MarketplaceItemResource;
use App\Shared\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HarvestListingController extends Controller
{
    public function __construct(
        private readonly CreateHarvestListingAction $createHarvestListingAction
    ) {}

    public function store(StoreHarvestListingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateHarvestListingDTO(
            farmerId: $request->user()->id,
            title: $validated['title'],
            description: $validated['description'] ?? null,
            cropName: $validated['crop_name'],
            quantityKg: (float) $validated['quantity_kg'],
            pricePerKg: (float) $validated['price_per_kg'],
            estimatedHarvestDate: $validated['estimated_harvest_date'] ?? null,
            shelfLifeDays: (int) $validated['shelf_life_days'],
            isHarvestAvailable: (bool) $validated['is_harvest_available'],
            farmId: isset($validated['farm_id']) ? (int) $validated['farm_id'] : null,
            plotId: isset($validated['plot_id']) ? (int) $validated['plot_id'] : null
        );

        $listing = $this->createHarvestListingAction->execute($dto);

        return response()->json([
            'message' => 'Harvest listing created successfully',
            'listing' => $listing,
        ], HttpCode::CREATED);
    }

    public function cancel(HarvestListing $listing, Request $request): JsonResponse
    {
        if ($listing->farmer_id !== $request->user()->id) {
            abort(HttpCode::FORBIDDEN, 'You are not authorized to cancel this listing.');
        }

        $listing->update(['status' => ContractStatus::CANCELLED]);

        $listing->load('farmer.farms', 'farm:id,verification_status', 'plot:id,verification_status');

        return response()->json([
            'message' => 'Listing cancelled successfully.',
            'data' => new MarketplaceItemResource($listing),
        ], HttpCode::OK);
    }
}
