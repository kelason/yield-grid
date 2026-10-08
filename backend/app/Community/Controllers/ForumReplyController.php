<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\StoreReplyRequest;
use App\Community\Requests\UpdateReplyRequest;
use App\Community\Resources\ForumReplyResource;
use App\Constants\HttpCode;
use App\Domain\Community\Actions\AcceptReplyAction;
use App\Domain\Community\Actions\CreateReplyAction;
use App\Domain\Community\DTOs\CreateReplyData;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Policies\ForumContentPolicy;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ForumReplyController
{
    public function store(StoreReplyRequest $request, ForumThread $thread, CreateReplyAction $action): ForumReplyResource
    {
        abort_if(! ForumContentPolicy::isThreadVisible($thread), HttpCode::NOT_FOUND);

        if ($thread->is_locked) {
            abort(HttpCode::FORBIDDEN, 'Thread is locked.');
        }

        try {
            $reply = $action->execute(new CreateReplyData(
                threadId: $thread->id,
                userId: $request->user()->id,
                body: $request->validated('body'),
                isAnonymous: (bool) $request->validated('is_anonymous', false),
                parentId: $request->validated('parent_id') ? (int) $request->validated('parent_id') : null,
            ));
        } catch (InvalidArgumentException $e) {
            abort(HttpCode::UNPROCESSABLE_ENTITY, $e->getMessage());
        }

        $reply->load(['author', 'attachments', 'votes']);

        return collect([new ForumReplyResource($reply)])->first();
    }

    public function update(UpdateReplyRequest $request, ForumReply $reply): ForumReplyResource
    {
        abort_if(! ForumContentPolicy::isReplyVisible($reply), HttpCode::NOT_FOUND);

        $this->authorizeUpdate($request->user(), $reply);

        $reply->update($request->only(['body']));

        return collect([new ForumReplyResource($reply->fresh(['author', 'attachments', 'votes']))])->first();
    }

    public function destroy(Request $request, ForumReply $reply): JsonResponse
    {
        // Owner delete survives moderation, but hidden replies stay invisible to others.
        abort_if(! ForumContentPolicy::isReplyVisible($reply) && $request->user()->id !== $reply->user_id, HttpCode::NOT_FOUND);

        $this->authorizeUpdate($request->user(), $reply);

        // Decrease thread reply count
        $reply->thread()->decrement('reply_count');

        $reply->delete();

        return response()->json(null, HttpCode::NO_CONTENT);
    }

    public function accept(Request $request, ForumReply $reply, AcceptReplyAction $action): JsonResponse
    {
        $thread = $reply->thread;

        if (! $thread instanceof ForumThread || ! ForumContentPolicy::isThreadVisible($thread)) {
            abort(HttpCode::NOT_FOUND);
        }

        abort_if(! ForumContentPolicy::isReplyVisible($reply, $thread), HttpCode::NOT_FOUND);

        if ($request->user()->id !== $thread->user_id) {
            abort(HttpCode::FORBIDDEN, 'Only the thread author can accept replies.');
        }

        $action->execute($thread->id, $reply->id);

        return response()->json(['message' => 'Accepted status updated.']);
    }

    private function authorizeUpdate(User $user, ForumReply $reply): void
    {
        if ($user->id !== $reply->user_id) {
            abort(HttpCode::FORBIDDEN, 'Unauthorized.');
        }
        if ($reply->thread->is_locked) {
            abort(HttpCode::FORBIDDEN, 'Thread is locked.');
        }
    }
}
