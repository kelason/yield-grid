<?php

declare(strict_types=1);

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Events\NewChatMessage;
use App\Domain\Chat\Models\ChatMessage;
use Illuminate\Support\Facades\DB;

final class SendMessageAction
{
    public function execute(int $conversationId, int $senderId, string $body): ChatMessage
    {
        return DB::transaction(function () use ($conversationId, $senderId, $body) {
            $message = ChatMessage::create([
                'conversation_id' => $conversationId,
                'user_id' => $senderId,
                'body' => $body,
            ]);

            $message->conversation->update([
                'last_message_at' => now(),
            ]);

            event(new NewChatMessage($message));

            return $message;
        });
    }
}
