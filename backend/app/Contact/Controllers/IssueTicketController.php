<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Contact\Requests\IssueTicketFilterRequest;
use App\Contact\Requests\StoreIssueTicketRequest;
use App\Contact\Resources\IssueTicketResource;
use App\Domain\Contact\Actions\CreateIssueTicketAction;
use App\Domain\Contact\Models\IssueTicket;
use App\Domain\Contact\Repositories\IssueTicketRepositoryInterface;
use App\Shared\Controllers\Controller;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

final class IssueTicketController extends Controller
{
    public function __construct(
        private readonly IssueTicketRepositoryInterface $issues,
    ) {}

    public function index(IssueTicketFilterRequest $request): JsonResponse
    {
        $this->authorize('viewAny', IssueTicket::class);

        /** @var User $reporter */
        $reporter = $request->user();
        $validated = $request->validated();

        $filters = [];

        if (($validated['status'] ?? null) !== null) {
            $filters['status'] = (string) $validated['status'];
        }

        $tickets = $this->issues->paginateForReporter(
            $reporter->id,
            $filters,
            (int) ($validated['per_page'] ?? PaginationConstants::DEFAULT_PER_PAGE)
        );

        return IssueTicketResource::collection($tickets)->response();
    }

    public function show(Request $request, int $issue): JsonResponse
    {
        /** @var User $reporter */
        $reporter = $request->user();
        $found = $this->issues->findForReporter($issue, $reporter->id);

        $this->authorize('view', $found);

        return (new IssueTicketResource($found))->response();
    }

    public function store(StoreIssueTicketRequest $request, CreateIssueTicketAction $action): JsonResponse
    {
        $this->authorize('create', IssueTicket::class);

        /** @var User $reporter */
        $reporter = $request->user();

        try {
            $ticket = $action->execute($reporter, $request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $status = $ticket->wasRecentlyCreated ? HttpCode::CREATED : HttpCode::OK;

        return (new IssueTicketResource($ticket))->response()->setStatusCode($status);
    }
}
