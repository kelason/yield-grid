<?php

use App\Domain\Chat\Models\ChatParticipant;
use Domain\Farming\Models\Plot;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('plot.{plotId}', function ($user, $plotId) {
    $plot = Plot::with('farm')->find((int) $plotId);

    if (! $plot || ! $plot->farm) {
        return false;
    }

    return (int) $plot->farm->user_id === (int) $user->id;
});

// Forum thread channel — any authenticated user can listen
Broadcast::channel('thread.{threadId}', function ($user, $threadId) {
    return $user !== null;
});

// Chat conversation — only participants
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return ChatParticipant::where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});
