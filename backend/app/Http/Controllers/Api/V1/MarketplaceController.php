<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Constants\PaginationConstants;
use App\Domain\Marketplace\Models\ForwardContract;
use App\Http\Controllers\Controller;
use App\Http\Resources\ForwardContractResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MarketplaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ForwardContract::available()->with(['farmer.farms']);

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

        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('total_price', 'asc'),
            'price_desc' => $query->orderBy('total_price', 'desc'),
            'harvest_soonest' => $query->orderBy('estimated_harvest_date', 'asc'),
            default => $query->latest(),
        };

        $perPage = (int) $request->query('per_page', PaginationConstants::MARKETPLACE_PER_PAGE);

        return ForwardContractResource::collection($query->paginate($perPage));
    }

    public function show(ForwardContract $contract): ForwardContractResource
    {
        $contract->load('farmer.farms');

        return new ForwardContractResource($contract);
    }
}
