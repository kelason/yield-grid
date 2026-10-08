<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Requests\ContactInboxFilterRequest;
use App\Admin\Requests\QueueContactReplyRequest;
use App\Admin\Resources\AdminContactMessageResource;
use App\Admin\Resources\ContactMessageReplyResource;
use App\Constants\AdminConstants;
use App\Constants\ContactConstants;
use App\Constants\HttpCode;
use App\Constants\PaginationConstants;
use App\Domain\Contact\Actions\QueueContactReplyAction;
use App\Domain\Contact\Actions\RetryContactReplyAction;
use App\Domain\Contact\Actions\TransitionContactMessageAction;
use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use App\Domain\Contact\Repositories\ContactMessageReplyRepositoryInterface;
use App\Jobs\SendContactReplyJob;
use App\Shared\Controllers\Controller;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Throwable;

final class AdminContactMessageController extends Controller
{
    public function index(ContactInboxFilterRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ContactMessage::class);

        $messages = $this->filteredQuery($request->validated())
            ->with('replies')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($request->validated('per_page') ?? PaginationConstants::DEFAULT_PER_PAGE));

        return AdminContactMessageResource::collection($messages)->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    public function show(ContactMessage $message): JsonResponse
    {
        $this->authorize('view', $message);

        $message->load('replies');

        return $this->messageResponse($message);
    }

    public function read(Request $request, ContactMessage $message, TransitionContactMessageAction $action): JsonResponse
    {
        $this->authorize('read', $message);

        /** @var User $actor */
        $actor = $request->user();

        return $this->transition($actor, $message, $action, ContactStatus::READ, ContactStatus::UNREAD);
    }

    public function close(Request $request, ContactMessage $message, TransitionContactMessageAction $action): JsonResponse
    {
        $this->authorize('close', $message);

        /** @var User $actor */
        $actor = $request->user();

        return $this->transition($actor, $message, $action, ContactStatus::CLOSED);
    }

    public function reopen(Request $request, ContactMessage $message, TransitionContactMessageAction $action): JsonResponse
    {
        $this->authorize('reopen', $message);

        /** @var User $actor */
        $actor = $request->user();

        return $this->transition($actor, $message, $action, ContactStatus::READ, ContactStatus::CLOSED);
    }

    public function storeReply(
        QueueContactReplyRequest $request,
        ContactMessage $message,
        QueueContactReplyAction $action,
    ): JsonResponse {
        $this->authorize('reply', $message);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $reply = $action->execute(
                $actor,
                $message,
                (string) $request->validated('body'),
                (string) $request->validated('client_request_id'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::UNPROCESSABLE_ENTITY);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->replyResponse($this->dispatchDelivery($reply));
    }

    public function retryReply(
        Request $request,
        ContactMessage $message,
        ContactMessageReply $reply,
        RetryContactReplyAction $action,
    ): JsonResponse {
        $this->authorize('retry', $message);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $retried = $action->execute($actor, $message, $reply);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found.'], HttpCode::NOT_FOUND);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        return $this->replyResponse($this->dispatchDelivery($retried), $action->wasStaleSending());
    }

    private function transition(
        User $actor,
        ContactMessage $message,
        TransitionContactMessageAction $action,
        ContactStatus $next,
        ?ContactStatus $from = null,
    ): JsonResponse {
        try {
            $updated = $action->execute($actor, $message, $next, $from);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], HttpCode::CONFLICT);
        }

        $updated->load('replies');

        return $this->messageResponse($updated);
    }

    private function dispatchDelivery(ContactMessageReply $reply): ContactMessageReply
    {
        try {
            app(BusDispatcher::class)->dispatch(new SendContactReplyJob($reply->id));
        } catch (Throwable) {
            $this->markEnqueueFailed($reply->id);
        }

        return $reply->refresh();
    }

    private function markEnqueueFailed(int $replyId): void
    {
        $replies = app(ContactMessageReplyRepositoryInterface::class);

        DB::transaction(function () use ($replies, $replyId): void {
            try {
                $probe = $replies->findById($replyId);
            } catch (ModelNotFoundException) {
                return;
            }

            ContactMessage::where('id', $probe->message_id)->lockForUpdate()->first();
            $locked = $replies->findLockedById($replyId);

            if ($locked->delivery_status !== ReplyDeliveryStatus::QUEUED) {
                return;
            }

            $locked->forceFill([
                'delivery_status' => ReplyDeliveryStatus::FAILED,
                'sending_started_at' => null,
                'error_code' => ContactConstants::REPLY_ERROR_TRANSPORT_FAILED,
            ]);
            $replies->save($locked);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ContactMessage>
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = ContactMessage::query();

        if (($filters['status'] ?? null) !== null) {
            $query->where('status', $filters['status']);
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $query->where(function (Builder $nested) use ($filters): void {
                $like = '%'.$this->escapeLike((string) $filters['search']).'%';
                $nested->where('name', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like)
                    ->orWhere('subject', 'ilike', $like)
                    ->orWhere('message', 'ilike', $like);
            });
        }

        return $query;
    }

    private function escapeLike(string $search): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    }

    private function messageResponse(ContactMessage $message): JsonResponse
    {
        return (new AdminContactMessageResource($message))->response()
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }

    private function replyResponse(ContactMessageReply $reply, bool $staleSending = false): JsonResponse
    {
        $resource = new ContactMessageReplyResource($reply);

        if ($staleSending) {
            $resource->additional(['meta' => [
                'duplicate_delivery_possible' => true,
                'warning' => ContactConstants::REPLY_STALE_SENDING_WARNING,
            ]]);
        }

        return $resource->response()->setStatusCode(HttpCode::ACCEPTED)
            ->header('Cache-Control', AdminConstants::CACHE_CONTROL_NO_STORE);
    }
}
