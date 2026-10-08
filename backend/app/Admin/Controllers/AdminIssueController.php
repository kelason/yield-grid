<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\AdminIssueFilterRequest;
use App\Admin\Requests\TransitionIssueTicketRequest;
use App\Admin\Resources\AdminIssueResource;
use App\Constants\AdminConstants;
use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Contact\Actions\TransitionIssueTicketAction;
use App\Domain\Contact\Enums\IssueStatus;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use LogicException;

final class AdminIssueController extends Controller
{
    public function __construct(
        private readonly IssueTicketRepositoryInterface $issues,
    ) {}

    public function index(AdminIssueFilterRequest $request): JsonResponse
    {
        $this->authorize('viewAny', IssueTicket::class);

        $validated = $request->validated();

        $tickets = $this->issues->paginateForAdmin(
            $this->repositoryFilters($validated),
            (int) ($validated['per_page'] ?? PaginationConstants::DEFAULT_PER_PAGE)
        );

        return AdminIssueResource::collection($tickets)->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    public function show(int $issue): JsonResponse
    {
        $found = $this->issues->findById($issue);

        $this->authorize('view', $found);

        return $this->issueResponse($found);
    }

    public function transition(
        TransitionIssueTicketRequest $request,
        int $issue,
        TransitionIssueTicketAction $action,
    ): JsonResponse {
        $found = $this->issues->findById($issue);

        $this->authorize('transition', $found);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $transitioned = $action->execute(
                $actor,
                $found,
                IssueStatus::from((string) $request->validated('status')),
                $request->validated('resolution'),
                (int) $request->validated('expected_version'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return $this->conflictResponse($issue, $e);
        }

        return $this->issueResponse($transitioned);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{status?: string, category?: string, search?: string}
     */
    private function repositoryFilters(array $validated): array
    {
        $filters = [];

        foreach (['status', 'category', 'search'] as $key) {
            if (($validated[$key] ?? null) !== null) {
                $filters[$key] = (string) $validated[$key];
            }
        }

        return $filters;
    }

    private function issueResponse(IssueTicket $issue): JsonResponse
    {
        return (new AdminIssueResource($issue))->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    private function conflictResponse(int $issueId, LogicException $exception): JsonResponse
    {
        $currentVersion = (int) $this->issues->findById($issueId)->version;

        return response()->json([
            'message' => $exception->getMessage(),
            'current_version' => $currentVersion,
        ], HttpCode::CONFLICT);
    }
}
