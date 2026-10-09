<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\AdminVerificationFilterRequest;
use App\Admin\Resources\AdminVerifiableResource;
use App\Constants\AdminConstants;
use App\Constants\PaginationConstants;
use App\Policies\FarmVerificationPolicy;
use App\Shared\Controllers\Controller;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class AdminFarmVerificationController extends Controller
{
    public function index(AdminVerificationFilterRequest $request): JsonResponse
    {
        $this->authorize(FarmVerificationPolicy::VIEW_ANY_ABILITY);

        $scope = (string) ($request->validated('scope') ?? AdminConstants::VERIFICATION_SCOPE_FARMS);
        $status = (string) ($request->validated('status') ?? VerificationStatus::PENDING->value);
        $perPage = (int) ($request->validated('per_page') ?? PaginationConstants::DEFAULT_PER_PAGE);

        $paginator = $scope === AdminConstants::VERIFICATION_SCOPE_PLOTS
            ? $this->plotQueue($status, $perPage)
            : $this->farmQueue($status, $perPage);

        return AdminVerifiableResource::collection($paginator)->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    public function showFarm(Farm $farm): JsonResponse
    {
        $this->authorize(FarmVerificationPolicy::VIEW_ABILITY, $farm);

        $farm->loadMissing('user')->loadCount('plots');

        return $this->resourceResponse(new AdminVerifiableResource($farm));
    }

    public function showPlot(Plot $plot): JsonResponse
    {
        $this->authorize(FarmVerificationPolicy::VIEW_ABILITY, $plot);

        $loaded = Plot::with('farm.user')
            ->selectRaw('plots.*, ST_AsGeoJSON(polygon) as geojson')
            ->findOrFail($plot->id);

        return $this->resourceResponse(new AdminVerifiableResource($loaded));
    }

    private function farmQueue(string $status, int $perPage): LengthAwarePaginator
    {
        return Farm::with('user')
            ->withCount('plots')
            ->where('verification_status', $status)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function plotQueue(string $status, int $perPage): LengthAwarePaginator
    {
        return Plot::with('farm.user')
            ->where('verification_status', $status)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function resourceResponse(AdminVerifiableResource $resource): JsonResponse
    {
        return $resource->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }
}
