<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

final class MarketplaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $contractsQuery = ForwardContract::available()->with(['farmer.farms', 'recommendation']);
        $listingsQuery = HarvestListing::available()->with(['farmer.farms']);

        // Apply filters to both queries
        $queries = [$contractsQuery, $listingsQuery];
        foreach ($queries as $query) {
            if ($request->has('crop')) {
                $query->where('crop_name', 'ilike', '%'.$request->query('crop').'%');
            }
            if ($request->has('min_price')) {
                $query->where('total_price', '>=', $request->query('min_price'));
            }
            if ($request->has('max_price')) {
                $query->where('total_price', '<=', $request->query('max_price'));
            }
            if ($request->has('harvest_after')) {
                $query->where('estimated_harvest_date', '>=', $request->query('harvest_after'));
            }
            if ($request->has('harvest_before')) {
                $query->where('estimated_harvest_date', '<=', $request->query('harvest_before'));
            }
        }

        // Apply availability filter specific to HarvestListing
        $availability = $request->query('availability', 'all');
        if ($availability === 'available') {
            // ForwardContracts are never "already harvested"
            $contractsQuery->whereRaw('1 = 0');
            $listingsQuery->where('is_harvest_available', true);
        } elseif ($availability === 'incoming') {
            // ForwardContracts are always incoming
            $listingsQuery->where('is_harvest_available', false);
        }

        $contracts = $contractsQuery->get();
        $listings = $listingsQuery->get();
        $all = $contracts->concat($listings);

        $sort = $request->query('sort', 'newest');
        $all = match ($sort) {
            'price_asc' => $all->sortBy('total_price'),
            'price_desc' => $all->sortByDesc('total_price'),
            'harvest_soonest' => $all->sortBy('estimated_harvest_date'),
            'harvest_available' => $all->sortByDesc(fn ($item) => $item instanceof HarvestListing ? $item->is_harvest_available : false)->values(),
            'incoming_harvest' => $all->sortBy(fn ($item) => $item instanceof HarvestListing ? $item->is_harvest_available : false)->values(),
            default => $all->sortByDesc('created_at'),
        };

        $perPage = (int) $request->query('per_page', PaginationConstants::MARKETPLACE_PER_PAGE);

        // Manual pagination
        $page = Paginator::resolveCurrentPage() ?: 1;
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($items, $all->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);

        return MarketplaceItemResource::collection($paginator);
    }

    public function show(Request $request, string $type, int $id): MarketplaceItemResource
    {
        if ($type === 'listing') {
            $item = HarvestListing::with('farmer.farms')->findOrFail($id);
        } else {
            $item = ForwardContract::with(['farmer.farms', 'recommendation'])->findOrFail($id);
        }

        return new MarketplaceItemResource($item);
    }
}
