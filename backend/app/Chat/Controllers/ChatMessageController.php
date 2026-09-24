<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Chat\Requests\SendMessageRequest;
use App\Chat\Resources\ChatMessageResource;
use App\Constants\HttpCode;
use App\Domain\Chat\Actions\SendMessageAction;
use App\Domain\Chat\Models\ChatConversation;

class ChatMessageController
{
    public function store(SendMessageRequest $request, ChatConversation $conversation, SendMessageAction $action): ChatMessageResource
    {
        if (! $conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(HttpCode::FORBIDDEN, 'Unauthorized.');
        }

        $message = $action->execute(
            $conversation->id,
            $request->user()->id,
            $request->validated('body')
        );

        $message->load(['sender', 'attachments']);

        return collect([new ChatMessageResource($message)])->first();
    }
}
