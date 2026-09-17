<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Actions\PublishContractAction;
use App\Domain\Marketplace\DTOs\PublishContractDTO;
use App\Domain\Marketplace\Enums\ContractStatus;
use App\Domain\Marketplace\Exceptions\ContractPublishingException;
use App\Domain\Marketplace\Exceptions\UnauthorizedContractPublishingException;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublishContractRequest;
use App\Http\Resources\ForwardContractResource;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ForwardContractController extends Controller
{
    public function __construct(
        private readonly ForwardContractRepositoryInterface $contractRepository
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ForwardContract::class);

        $query = ForwardContract::byFarmer($request->user()->id)->latest();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = (int) $request->query('per_page', PaginationConstants::DEFAULT_PER_PAGE);

        return ForwardContractResource::collection($query->paginate($perPage));
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ForwardContract::class);

        $stats = $this->contractRepository->getFarmerStats($request->user()->id);

        return response()->json(['data' => $stats]);
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

        try {
            $contract = $action->execute($dto);
        } catch (UnauthorizedContractPublishingException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::FORBIDDEN);
        } catch (ContractPublishingException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'recommendation' => [$e->getMessage()],
                ],
            ], HttpCode::UNPROCESSABLE_ENTITY);
        }

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
