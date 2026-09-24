<?php

declare(strict_types=1);

namespace App\Chat\Resources;

use App\Domain\Chat\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatMessage
 */
class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_own' => $this->user_id === $userId,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar_url' => $this->sender->avatar_url,
            ],
            'attachments' => ChatAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
        ];
    }
}
