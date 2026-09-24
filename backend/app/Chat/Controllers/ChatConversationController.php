<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Chat\Requests\StartConversationRequest;
use App\Chat\Resources\ChatConversationResource;
use App\Chat\Resources\ChatMessageResource;
use App\Constants\ChatConstants;
use App\Constants\HttpCode;
use App\Domain\Chat\Actions\FindOrCreateConversationAction;
use App\Domain\Chat\Actions\MarkConversationReadAction;
use App\Domain\Chat\Models\ChatConversation;
use Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChatConversationController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $conversations = ChatConversation::forUser($userId)
            ->with(['participants.user', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        return ChatConversationResource::collection($conversations);
    }

    public function show(Request $request, ChatConversation $conversation, MarkConversationReadAction $action): AnonymousResourceCollection
    {
        $this->authorizeAccess($request->user(), $conversation);

        // Mark as read
        $action->execute($conversation->id, $request->user()->id);

        $messages = $conversation->messages()
            ->with(['sender', 'attachments'])
            ->latest() // Important: latest first for pagination
            ->paginate(ChatConstants::MESSAGES_PER_PAGE);

        return ChatMessageResource::collection($messages);
    }

    public function store(StartConversationRequest $request, FindOrCreateConversationAction $action): ChatConversationResource
    {
        $conversation = $action->execute(
            $request->user()->id,
            (int) $request->validated('recipient_id')
        );

        $conversation->load(['participants.user', 'latestMessage']);

        return collect([new ChatConversationResource($conversation)])->first();
    }

    private function authorizeAccess(User $user, ChatConversation $conversation): void
    {
        if (! $conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(HttpCode::FORBIDDEN, 'Unauthorized.');
        }
    }
}
