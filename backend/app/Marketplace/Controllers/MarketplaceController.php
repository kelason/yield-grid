<?php

declare(strict_types=1);

namespace App\Marketplace\Controllers;

use App\Constants\GeoConstants;
use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Domain\Marketplace\Models\HarvestListing;
use App\Infrastructure\Services\PsgcService;
use App\Marketplace\Resources\MarketplaceItemResource;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

final class MarketplaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = $request->query('sort', 'newest');
        $withFarmer = $sort === 'nearest'
            ? ['farmer.farms', 'farmer.addresses' => fn ($q) => $q->where('is_default', true)]
            : ['farmer.farms'];

        $contractsQuery = ForwardContract::available()->with(array_merge($withFarmer, ['recommendation']));
        $listingsQuery = HarvestListing::available()->with($withFarmer);

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

        $all = match ($sort) {
            'price_asc' => $all->sortBy('total_price'),
            'price_desc' => $all->sortByDesc('total_price'),
            'harvest_soonest' => $all->sortBy('estimated_harvest_date'),
            'harvest_available' => $all->sortByDesc(fn ($item) => $item instanceof HarvestListing ? $item->is_harvest_available : false)->values(),
            'incoming_harvest' => $all->sortBy(fn ($item) => $item instanceof HarvestListing ? $item->is_harvest_available : false)->values(),
            'nearest' => $this->sortNearest($request, $all),
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

    /**
     * Nearest-first ordering by farmer default-address coordinates. Items
     * without coordinates sort last. Falls back to newest when the viewer
     * location is unknown.
     *
     * @param  Collection<int, ForwardContract|HarvestListing>  $items
     * @return Collection<int, ForwardContract|HarvestListing>
     */
    private function sortNearest(Request $request, Collection $items): Collection
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            $viewerAddress = $request->user()?->defaultAddress();
            if (! $viewerAddress instanceof UserAddress || $viewerAddress->latitude === null || $viewerAddress->longitude === null) {
                return $items->sortByDesc('created_at')->values();
            }
            $lat = (float) $viewerAddress->latitude;
            $lng = (float) $viewerAddress->longitude;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        return $items
            ->map(function (ForwardContract|HarvestListing $item) use ($lat, $lng) {
                $address = $item->farmer->addresses->first();
                if (! $address instanceof UserAddress || $address->latitude === null || $address->longitude === null) {
                    $item->setAttribute('distance_m', null);
                } else {
                    $item->setAttribute('distance_m', PsgcService::haversineKm(
                        $lat, $lng, (float) $address->latitude, (float) $address->longitude
                    ) * GeoConstants::METERS_PER_KM);
                }

                return $item;
            })
            ->sortBy(fn (ForwardContract|HarvestListing $item) => $item->getAttribute('distance_m') ?? PHP_FLOAT_MAX)
            ->values();
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
