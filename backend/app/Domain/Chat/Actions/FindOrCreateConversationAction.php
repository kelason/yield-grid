<?php

declare(strict_types=1);

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Models\ChatConversation;
use Illuminate\Support\Facades\DB;

final class FindOrCreateConversationAction
{
    public function execute(int $userOneId, int $userTwoId): ChatConversation
    {
        return DB::transaction(function () use ($userOneId, $userTwoId) {
            // Find existing conversation
            $conversation = ChatConversation::whereHas('participants', function ($q) use ($userOneId) {
                $q->where('user_id', $userOneId);
            })->whereHas('participants', function ($q) use ($userTwoId) {
                $q->where('user_id', $userTwoId);
            })->first();

            if ($conversation) {
                return $conversation;
            }

            // Create new conversation
            $conversation = ChatConversation::create();

            $conversation->participants()->createMany([
                ['user_id' => $userOneId],
                ['user_id' => $userTwoId],
            ]);

            return $conversation;
        });
    }
}
