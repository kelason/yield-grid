<?php

declare(strict_types=1);

namespace App\Community\Resources;

use App\Domain\Community\Models\ForumThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ForumThread
 */
class ForumThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'vote_score' => $this->vote_score,
            'reply_count' => $this->reply_count,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'has_accepted_reply' => $this->accepted_reply_id !== null,
            'author' => $this->is_anonymous ? [
                'name' => 'Anonymous Farmer',
                'avatar_url' => null,
                'role' => 'farmer',
            ] : [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'avatar_url' => $this->author->avatar_url,
                'role' => $this->author->role->value,
            ],
            'category' => new ForumCategoryResource($this->whenLoaded('category')),
            'tags' => ForumTagResource::collection($this->whenLoaded('tags')),
            'attachments' => ForumAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
            'last_activity_at' => $this->last_activity_at,
            'user_vote' => $this->when($userId !== null, function () use ($userId) {
                $vote = $this->resource->votes->firstWhere('user_id', $userId);

                return $vote ? $vote->value : 0;
            }),
            'replies' => ForumReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
