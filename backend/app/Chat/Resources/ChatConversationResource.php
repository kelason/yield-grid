<?php

declare(strict_types=1);

namespace App\Chat\Resources;

use App\Domain\Chat\Models\ChatConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatConversation
 */
class ChatConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        // Find the other participant
        $otherParticipant = $this->participants->firstWhere('user_id', '!==', $userId)?->user;

        // Find current user's participant record for unread count
        $myParticipant = $this->participants->firstWhere('user_id', $userId);

        return [
            'id' => $this->id,
            'other_participant' => $otherParticipant ? [
                'id' => $otherParticipant->id,
                'name' => $otherParticipant->name,
                'avatar_url' => $otherParticipant->avatar_url,
                'role' => $otherParticipant->role->value,
            ] : null,
            'latest_message' => new ChatMessageResource($this->whenLoaded('latestMessage')),
            'unread_count' => $myParticipant ? $myParticipant->unreadCount() : 0,
            'last_message_at' => $this->last_message_at,
        ];
    }
}
