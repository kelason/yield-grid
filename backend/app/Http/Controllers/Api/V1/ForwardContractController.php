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
use App\Domain\Marketplace\Models\HarvestListing;
use App\Domain\Marketplace\Repositories\ForwardContractRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublishContractRequest;
use App\Http\Resources\ForwardContractResource;
use App\Http\Resources\MarketplaceItemResource;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

final class ForwardContractController extends Controller
{
    public function __construct(
        private readonly ForwardContractRepositoryInterface $contractRepository
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ForwardContract::class);

        $contractsQuery = ForwardContract::byFarmer($request->user()->id)->with(['farmer.farms', 'recommendation']);
        $listingsQuery = HarvestListing::byFarmer($request->user()->id)->with(['farmer.farms']);

        if ($request->has('status')) {
            $contractsQuery->where('status', $request->query('status'));
            $listingsQuery->where('status', $request->query('status'));
        }

        $contracts = $contractsQuery->get();
        $listings = $listingsQuery->get();
        $all = $contracts->concat($listings)->sortByDesc('created_at')->values();

        $perPage = (int) $request->query('per_page', PaginationConstants::DEFAULT_PER_PAGE);

        $page = Paginator::resolveCurrentPage() ?: 1;
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($items, $all->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);

        return MarketplaceItemResource::collection($paginator);
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
