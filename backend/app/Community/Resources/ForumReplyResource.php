<?php

declare(strict_types=1);

namespace App\Community\Resources;

use App\Domain\Community\Models\ForumReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ForumReply
 */
class ForumReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'vote_score' => $this->vote_score,
            'is_accepted' => $this->is_accepted,
            'parent_id' => $this->parent_id,
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
            'attachments' => ForumAttachmentResource::collection($this->whenLoaded('attachments')),
            'children' => ForumReplyResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'user_vote' => $this->when($userId !== null, function () use ($userId) {
                $vote = $this->resource->votes->firstWhere('user_id', $userId);

                return $vote ? $vote->value : 0;
            }),
        ];
    }
}
