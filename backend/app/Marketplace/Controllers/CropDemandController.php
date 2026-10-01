<?php

declare(strict_types=1);

namespace App\Marketplace\Controllers;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Actions\CancelDemandAction;
use App\Domain\Marketplace\Actions\CreateCropDemandAction;
use App\Domain\Marketplace\DTOs\PublishDemandDTO;
use App\Domain\Marketplace\Enums\DemandOfferStatus;
use App\Domain\Marketplace\Enums\DemandStatus;
use App\Domain\Marketplace\Models\CropDemand;
use App\Domain\Marketplace\Models\CropDemandOffer;
use App\Marketplace\Requests\StoreDemandRequest;
use App\Marketplace\Resources\CropDemandResource;
use App\Shared\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LogicException;

final class CropDemandController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CropDemand::open()->with(['buyer', 'deliveryAddress'])->withCount('offers');

        if ($request->filled('crop')) {
            $query->where('crop_demands.crop_name', 'ilike', '%'.$request->query('crop').'%');
        }
        if ($request->filled('min_budget')) {
            $query->where('crop_demands.total_budget', '>=', $request->query('min_budget'));
        }
        if ($request->filled('max_budget')) {
            $query->where('crop_demands.total_budget', '<=', $request->query('max_budget'));
        }
        if ($request->filled('needed_before')) {
            $query->where('crop_demands.needed_by_date', '<=', $request->query('needed_before'));
        }
        if ($request->filled('needed_after')) {
            $query->where('crop_demands.needed_by_date', '>=', $request->query('needed_after'));
        }

        $sort = (string) $request->query('sort', 'newest');

        if ($sort === 'nearest') {
            $this->applyNearestSort($request, $query);
        } else {
            $query = match ($sort) {
                'budget_asc' => $query->orderBy('crop_demands.total_budget'),
                'budget_desc' => $query->orderByDesc('crop_demands.total_budget'),
                'needed_soonest' => $query->orderBy('crop_demands.needed_by_date'),
                default => $query->orderByDesc('crop_demands.created_at'),
            };
        }

        $perPage = (int) $request->query('per_page', PaginationConstants::MARKETPLACE_PER_PAGE);

        return CropDemandResource::collection($query->paginate($perPage));
    }

    /**
     * @param  Builder<CropDemand>  $query
     */
    private function applyNearestSort(Request $request, Builder $query): void
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (is_numeric($lat) && is_numeric($lng)) {
            $query->nearestTo((float) $lat, (float) $lng);

            return;
        }

        $viewerAddress = $request->user()?->defaultAddress();
        if ($viewerAddress !== null) {
            $query->closestToAddress($viewerAddress);

            return;
        }

        $query->orderByDesc('crop_demands.created_at');
    }

    public function show(Request $request, CropDemand $demand): CropDemandResource
    {
        $viewer = $request->user();
        $isBuyer = $viewer !== null && $viewer->id === $demand->buyer_id;

        $demand->load(['buyer', 'deliveryAddress']);

        if ($isBuyer) {
            $demand->load(['offers.farmer', 'offers.purchase']);
        }

        $includeDeliveryAddress = $isBuyer;
        if (! $isBuyer && $viewer !== null) {
            $includeDeliveryAddress = CropDemandOffer::where('crop_demand_id', $demand->id)
                ->where('farmer_id', $viewer->id)
                ->chatEligible()
                ->exists();
        }

        return new CropDemandResource($demand, $includeDeliveryAddress);
    }

    public function store(StoreDemandRequest $request, CreateCropDemandAction $action): JsonResponse
    {
        $this->authorize('create', CropDemand::class);

        $demand = $action->execute(PublishDemandDTO::fromRequest($request->user()->id, $request->validated()));
        $demand->load(['buyer', 'deliveryAddress']);

        return (new CropDemandResource($demand, true))->response()->setStatusCode(HttpCode::CREATED);
    }

    public function myDemands(Request $request): AnonymousResourceCollection
    {
        $statuses = collect(explode(',', (string) $request->query('status', '')))
            ->map(fn (string $status): ?string => DemandStatus::tryFrom(trim($status))?->value)
            ->filter()
            ->values()
            ->all();

        $demands = CropDemand::byBuyer($request->user()->id)
            ->with(['deliveryAddress'])
            ->withCount(['offers', 'offers as pending_offers_count' => fn ($query) => $query->where('status', DemandOfferStatus::PENDING)])
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', PaginationConstants::MARKETPLACE_PER_PAGE));

        return CropDemandResource::collection($demands);
    }

    public function cancel(Request $request, CropDemand $demand, CancelDemandAction $action): CropDemandResource|JsonResponse
    {
        $this->authorize('cancel', $demand);

        try {
            $demand = $action->execute($demand);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $demand->load(['buyer', 'deliveryAddress']);

        return new CropDemandResource($demand, true);
    }
}
