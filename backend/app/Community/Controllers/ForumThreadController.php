<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\ForumFilterRequest;
use App\Community\Requests\StoreThreadRequest;
use App\Community\Requests\UpdateThreadRequest;
use App\Community\Resources\ForumThreadResource;
use App\Constants\ForumConstants;
use App\Constants\HttpCode;
use App\Domain\Community\Actions\CreateThreadAction;
use App\Domain\Community\DTOs\CreateThreadData;
use App\Domain\Community\Models\ForumThread;
use App\Policies\ForumContentPolicy;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ForumThreadController
{
    public function index(ForumFilterRequest $request): AnonymousResourceCollection
    {
        $threads = $this->filteredQuery($request)->paginate(ForumConstants::THREADS_PER_PAGE);

        ForumContentPolicy::applyVisibleCounts($threads->getCollection());

        return ForumThreadResource::collection($threads);
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
        abort_if(! ForumContentPolicy::isThreadVisible($thread), HttpCode::NOT_FOUND);

        $thread->load([
            'author',
            'category',
            'tags',
            'attachments',
            'votes',
            'replies' => function ($query) {
                // Only load top-level replies here, nested children loaded recursively or flat based on logic
                $query->whereNull('parent_id')->visible()
                    ->with(['author', 'votes', 'attachments', 'children' => function ($q) {
                        $q->visible()->with(['author', 'votes', 'attachments']);
                    }])
                    ->orderByDesc('is_accepted') // Accepted first
                    ->orderByDesc('vote_score')
                    ->orderBy('created_at');
            },
        ]);

        ForumContentPolicy::applyVisibleCounts(collect([$thread]));

        return collect([new ForumThreadResource($thread)])->first();
    }

    public function update(UpdateThreadRequest $request, ForumThread $thread): ForumThreadResource
    {
        abort_if(! ForumContentPolicy::isThreadVisible($thread), HttpCode::NOT_FOUND);

        $this->authorizeUpdate($request->user(), $thread);

        $thread->update($request->only(['title', 'body']));

        if ($request->has('tag_ids')) {
            $thread->tags()->sync($request->validated('tag_ids'));
        }

        return collect([new ForumThreadResource($thread->fresh(['author', 'category', 'tags']))])->first();
    }

    public function destroy(Request $request, ForumThread $thread): JsonResponse
    {
        // Owner delete survives moderation, but hidden threads stay invisible to others.
        abort_if(! ForumContentPolicy::isThreadVisible($thread) && $request->user()->id !== $thread->user_id, HttpCode::NOT_FOUND);

        $this->authorizeUpdate($request->user(), $thread);
        $thread->delete();

        return response()->json(null, HttpCode::NO_CONTENT);
    }

    /**
     * @return Builder<ForumThread>
     */
    private function filteredQuery(ForumFilterRequest $request): Builder
    {
        $query = ForumThread::query()->visible()
            ->with(['author', 'category', 'tags', 'votes'])
            ->withCount('replies');

        $this->applyFilters($query, $request);
        $this->applySort($query, (string) $request->input('sort', 'latest'));

        // Always put pinned threads first
        $query->orderByDesc('is_pinned');

        return $query;
    }

    /**
     * @param  Builder<ForumThread>  $query
     */
    private function applyFilters(Builder $query, ForumFilterRequest $request): void
    {
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
    }

    /**
     * @param  Builder<ForumThread>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'most_voted' => $query->orderByDesc('vote_score'),
            'most_replied' => $query->orderByDesc('reply_count'),
            'unanswered' => $query->where('reply_count', 0)->orderByDesc('last_activity_at'),
            default => $query->orderByDesc('last_activity_at'), // 'latest'
        };
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
