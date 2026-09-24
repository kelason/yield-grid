<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\StoreThreadRequest;
use App\Community\Requests\UpdateThreadRequest;
use App\Community\Resources\ForumThreadResource;
use App\Constants\ForumConstants;
use App\Constants\HttpCode;
use App\Domain\Community\Actions\CreateThreadAction;
use App\Domain\Community\DTOs\CreateThreadData;
use App\Domain\Community\Models\ForumThread;
use Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ForumThreadController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ForumThread::query()
            ->with(['author', 'category', 'tags', 'votes'])
            ->withCount('replies');

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category'));
            });
        }

        // Filter by tag
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->input('tag'));
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'most_voted' => $query->orderByDesc('vote_score'),
            'most_replied' => $query->orderByDesc('reply_count'),
            'unanswered' => $query->where('reply_count', 0)->orderByDesc('last_activity_at'),
            default => $query->orderByDesc('last_activity_at'), // 'latest'
        };

        // Always put pinned threads first
        $query->orderByDesc('is_pinned');

        return ForumThreadResource::collection(
            $query->paginate(ForumConstants::THREADS_PER_PAGE)
        );
    }

    public function store(StoreThreadRequest $request, CreateThreadAction $action): ForumThreadResource
    {
        $thread = $action->execute(new CreateThreadData(
            userId: $request->user()->id,
            categoryId: (int) $request->validated('category_id'),
            title: $request->validated('title'),
            body: $request->validated('body'),
            isAnonymous: (bool) $request->validated('is_anonymous', false),
            tagIds: $request->validated('tag_ids', []),
        ));

        // Load relations for resource
        $thread->load(['author', 'category', 'tags']);

        return collect([new ForumThreadResource($thread)])->first();
    }

    public function show(ForumThread $thread): ForumThreadResource
    {
        $thread->load([
            'author',
            'category',
            'tags',
            'attachments',
            'votes',
            'replies' => function ($query) {
                // Only load top-level replies here, nested children loaded recursively or flat based on logic
                $query->whereNull('parent_id')
                    ->with(['author', 'votes', 'attachments', 'children' => function ($q) {
                        $q->with(['author', 'votes', 'attachments']);
                    }])
                    ->orderByDesc('is_accepted') // Accepted first
                    ->orderByDesc('vote_score')
                    ->orderBy('created_at');
            },
        ]);

        return collect([new ForumThreadResource($thread)])->first();
    }

    public function update(UpdateThreadRequest $request, ForumThread $thread): ForumThreadResource
    {
        $this->authorizeUpdate($request->user(), $thread);

        $thread->update($request->only(['title', 'body']));

        if ($request->has('tag_ids')) {
            $thread->tags()->sync($request->validated('tag_ids'));
        }

        return collect([new ForumThreadResource($thread->fresh(['author', 'category', 'tags']))])->first();
    }

    public function destroy(Request $request, ForumThread $thread): JsonResponse
    {
        $this->authorizeUpdate($request->user(), $thread);
        $thread->delete();

        return response()->json(null, HttpCode::NO_CONTENT);
    }

    private function authorizeUpdate(User $user, ForumThread $thread): void
    {
        if ($user->id !== $thread->user_id) {
            abort(HttpCode::FORBIDDEN, 'Unauthorized.');
        }
        if ($thread->is_locked) {
            abort(HttpCode::FORBIDDEN, 'Thread is locked.');
        }
    }
}
