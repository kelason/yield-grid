<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Domain\Marketplace\Actions\CreateHarvestListingAction;
use App\Domain\Marketplace\DTOs\CreateHarvestListingDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HarvestListingController extends Controller
{
    public function __construct(
        private readonly CreateHarvestListingAction $createHarvestListingAction
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'crop_name' => 'required|string|max:100',
            'quantity_kg' => 'required|numeric|min:1',
            'price_per_kg' => 'required|numeric|min:0.01',
            'estimated_harvest_date' => 'required_if:is_harvest_available,false|nullable|date',
            'shelf_life_days' => 'required|integer|min:1',
            'is_harvest_available' => 'required|boolean',
        ]);

        $dto = new CreateHarvestListingDTO(
            farmerId: $request->user()->id,
            title: $validated['title'],
            description: $validated['description'] ?? null,
            cropName: $validated['crop_name'],
            quantityKg: (float) $validated['quantity_kg'],
            pricePerKg: (float) $validated['price_per_kg'],
            estimatedHarvestDate: $validated['estimated_harvest_date'] ?? null,
            shelfLifeDays: (int) $validated['shelf_life_days'],
            isHarvestAvailable: (bool) $validated['is_harvest_available']
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

        $listing->load('farmer.farms');

        return response()->json([
            'message' => 'Listing cancelled successfully.',
            'data' => new MarketplaceItemResource($listing),
        ], HttpCode::OK);
    }
}
