<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\AdminVerificationFilterRequest;
use App\Admin\Requests\RejectVerifiableRequest;
use App\Admin\Requests\VerifyVerifiableRequest;
use App\Admin\Resources\AdminVerifiableResource;
use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Policies\FarmVerificationPolicy;
use App\Shared\Controllers\Controller;
use Domain\Farming\Actions\RecordVerificationDecisionAction;
use Domain\Farming\Enums\VerificationDecision;
use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

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

    public function verifyFarm(VerifyVerifiableRequest $request, Farm $farm, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $farm, VerificationDecision::VERIFY,
            VerificationMethod::from((string) $request->validated('method')),
            $this->optionalString($request->validated('note')), $action);
    }

    public function verifyPlot(VerifyVerifiableRequest $request, Plot $plot, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $plot, VerificationDecision::VERIFY,
            VerificationMethod::from((string) $request->validated('method')),
            $this->optionalString($request->validated('note')), $action);
    }

    public function rejectFarm(RejectVerifiableRequest $request, Farm $farm, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $farm, VerificationDecision::REJECT, null,
            (string) $request->validated('reason'), $action);
    }

    public function rejectPlot(RejectVerifiableRequest $request, Plot $plot, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $plot, VerificationDecision::REJECT, null,
            (string) $request->validated('reason'), $action);
    }

    public function revokeFarm(RejectVerifiableRequest $request, Farm $farm, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $farm, VerificationDecision::REVOKE, null,
            (string) $request->validated('reason'), $action);
    }

    public function revokePlot(RejectVerifiableRequest $request, Plot $plot, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $plot, VerificationDecision::REVOKE, null,
            (string) $request->validated('reason'), $action);
    }

    public function reopenFarm(Request $request, Farm $farm, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $farm, VerificationDecision::REOPEN, null, null, $action);
    }

    public function reopenPlot(Request $request, Plot $plot, RecordVerificationDecisionAction $action): JsonResponse
    {
        return $this->decideAndRespond($request, $plot, VerificationDecision::REOPEN, null, null, $action);
    }

    private function decideAndRespond(Request $request, Farm|Plot $target, VerificationDecision $decision, ?VerificationMethod $method, ?string $note, RecordVerificationDecisionAction $action): JsonResponse
    {
        $this->authorize(FarmVerificationPolicy::DECIDE_ABILITY, $target);

        /** @var User $admin */
        $admin = $request->user();

        try {
            $result = $action->execute($target, $decision, $method, $note, $admin);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->resourceResponse(new AdminVerifiableResource($this->loadedResult($result)));
    }

    private function loadedResult(Farm|Plot $result): Farm|Plot
    {
        if ($result instanceof Farm) {
            return $result->loadMissing('user')->loadCount('plots');
        }

        return $result->loadMissing('farm.user');
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
