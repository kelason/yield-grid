<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Domain\CropRecommendation\Models\CropRecommendation;
use App\Domain\Marketplace\Actions\PublishContractAction;
use App\Domain\Marketplace\DTOs\PublishContractDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublishContractRequest;
use App\Http\Resources\ForwardContractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ForwardContractController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ForwardContract::class);

        $query = ForwardContract::byFarmer($request->user()->id)->latest();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        return ForwardContractResource::collection($query->paginate(15));
    }

    public function show(ForwardContract $contract): ForwardContractResource
    {
        $this->authorize('view', $contract);

        $contract->load('recommendation');

        return new ForwardContractResource($contract);
    }

    public function store(
        PublishContractRequest $request,
        CropRecommendation $recommendation,
        PublishContractAction $action
    ): JsonResponse {
        $this->authorize('create', ForwardContract::class);

        $dto = new PublishContractDTO(
            recommendationId: $recommendation->id,
            farmerId: $request->user()->id,
            title: $request->validated('title'),
            description: $request->validated('description'),
            quantityKg: (float) $request->validated('quantity_kg'),
            pricePerKg: (float) $request->validated('price_per_kg'),
            estimatedHarvestDate: $request->validated('estimated_harvest_date'),
            expiryDate: $request->validated('expiry_date'),
        );

        $contract = $action->execute($dto);

        return response()->json([
            'data' => new ForwardContractResource($contract),
        ], HttpCode::CREATED);
    }

    public function cancel(ForwardContract $contract): JsonResponse
    {
        $this->authorize('cancel', $contract);

        $contract->update(['status' => ContractStatus::CANCELLED]);

        return response()->json([
            'message' => 'Contract cancelled successfully.',
            'data' => new ForwardContractResource($contract->fresh()),
        ]);
    }
}
