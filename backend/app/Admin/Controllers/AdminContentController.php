<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\AdminContentFilterRequest;
use App\Admin\Requests\AdminReasonRequest;
use App\Admin\Resources\AdminContentResource;
use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Community\Actions\ModerateForumContentAction;
use App\Domain\Marketplace\Actions\ModerateMarketplaceContentAction;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Domain\Shared\Services\ContentTargetResolver;
use App\Policies\AdminContentPolicy;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use LogicException;

final class AdminContentController extends Controller
{
    public function __construct(
        private readonly ContentTargetResolver $targets,
        private readonly ModerateForumContentAction $forum,
        private readonly ModerateMarketplaceContentAction $marketplace,
    ) {}

    public function index(AdminContentFilterRequest $request, string $type): JsonResponse
    {
        $this->authorize(AdminContentPolicy::VIEW_ABILITY);

        $targetType = $this->resolveType($type);

        if (! $targetType instanceof ReportTargetType) {
            return $this->notFound();
        }

        $validated = $request->validated();

        $items = $this->targets->paginate(
            $targetType,
            [
                'search' => isset($validated['search']) ? (string) $validated['search'] : null,
                'visibility' => isset($validated['visibility']) ? (string) $validated['visibility'] : null,
            ],
            (int) ($validated['per_page'] ?? PaginationConstants::DEFAULT_PER_PAGE)
        );

        return AdminContentResource::collection($items)->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    public function show(string $type, string $id): JsonResponse
    {
        $this->authorize(AdminContentPolicy::VIEW_ABILITY);

        $target = $this->resolveTarget($type, $id);

        if (! $target instanceof Model) {
            return $this->notFound();
        }

        return $this->contentResponse($target);
    }

    public function hide(AdminReasonRequest $request, string $type, string $id): JsonResponse
    {
        return $this->moderate($request, $type, $id, true);
    }

    public function restore(AdminReasonRequest $request, string $type, string $id): JsonResponse
    {
        return $this->moderate($request, $type, $id, false);
    }

    private function moderate(AdminReasonRequest $request, string $type, string $id, bool $hide): JsonResponse
    {
        $this->authorize(AdminContentPolicy::MODERATE_ABILITY);

        $targetType = $this->resolveType($type);

        if (! $targetType instanceof ReportTargetType) {
            return $this->notFound();
        }

        /** @var User $actor */
        $actor = $request->user();
        $reason = (string) $request->validated('reason');

        try {
            $affected = $this->executeModeration($actor, $targetType, $id, $hide, $reason);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->moderationResponse($affected, $id);
    }

    private function executeModeration(
        User $actor,
        ReportTargetType $type,
        string $id,
        bool $hide,
        string $reason,
    ): Model {
        if ($this->isForumType($type)) {
            return $this->forum->execute($actor, $type, $id, $hide, $reason);
        }

        return $this->marketplace->execute($actor, $type, $id, $hide, $reason);
    }

    private function moderationResponse(Model $affected, string $selectedId): JsonResponse
    {
        return (new AdminContentResource($affected))
            ->additional(['meta' => [
                'selected_id' => $selectedId,
                'affected_root_id' => (string) $affected->getKey(),
            ]])
            ->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    private function resolveTarget(string $type, string $id): ?Model
    {
        $targetType = $this->resolveType($type);

        if (! $targetType instanceof ReportTargetType) {
            return null;
        }

        return $this->targets->findForAdmin($targetType, $id);
    }

    private function resolveType(string $type): ?ReportTargetType
    {
        return ReportTargetType::tryFrom($type);
    }

    private function isForumType(ReportTargetType $type): bool
    {
        return $type === ReportTargetType::THREAD || $type === ReportTargetType::REPLY;
    }

    private function contentResponse(Model $target): JsonResponse
    {
        return (new AdminContentResource($target))->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Resource not found.'], HttpCode::NOT_FOUND);
    }
}
