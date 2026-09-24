<?php

declare(strict_types=1);

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Events\ConversationRead;
use App\Domain\Chat\Models\ChatParticipant;

final class MarkConversationReadAction
{
    public function execute(int $conversationId, int $userId): void
    {
        $participant = ChatParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($participant) {
            $now = now();
            $participant->update(['last_read_at' => $now]);
            event(new ConversationRead($conversationId, $userId, $now));
        }
    }
}
