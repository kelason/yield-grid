<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\AdminReasonRequest;
use App\Admin\Requests\AdminUserFilterRequest;
use App\Admin\Resources\AdminUserResource;
use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Users\Actions\SuspendUserAction;
use App\Domain\Users\Actions\UnsuspendUserAction;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use LogicException;

final class AdminUserController extends Controller
{
    public function index(AdminUserFilterRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validated();

        if (array_key_exists('suspended', $filters) && $filters['suspended'] !== null) {
            $filters['suspended'] = $request->boolean('suspended');
        }

        $users = $this->filteredQuery($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($request->validated('per_page') ?? PaginationConstants::DEFAULT_PER_PAGE));

        return AdminUserResource::collection($users)->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->userResponse($user);
    }

    public function suspend(AdminReasonRequest $request, User $user, SuspendUserAction $action): JsonResponse
    {
        $this->authorize('suspend', $user);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $suspended = $action->execute($actor, $user, (string) $request->validated('reason'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->userResponse($suspended);
    }

    public function unsuspend(AdminReasonRequest $request, User $user, UnsuspendUserAction $action): JsonResponse
    {
        $this->authorize('unsuspend', $user);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $restored = $action->execute($actor, $user, (string) $request->validated('reason'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->userResponse($restored);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = User::query();

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $query->where(function (Builder $nested) use ($filters): void {
                $like = '%'.$this->escapeLike((string) $filters['search']).'%';
                $nested->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like);
            });
        }

        if (($filters['role'] ?? null) !== null) {
            $query->where('role', $filters['role']);
        }

        if (array_key_exists('suspended', $filters) && $filters['suspended'] !== null) {
            $query->when(
                (bool) $filters['suspended'],
                fn (Builder $only): Builder => $only->whereNotNull('suspended_at'),
                fn (Builder $only): Builder => $only->whereNull('suspended_at'),
            );
        }

        return $query;
    }

    private function escapeLike(string $search): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    }

    private function userResponse(User $user): JsonResponse
    {
        return (new AdminUserResource($user))->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }
}
