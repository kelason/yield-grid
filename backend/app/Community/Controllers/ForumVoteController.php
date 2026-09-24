<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Resources\ForumReplyResource;
use App\Community\Resources\ForumThreadResource;
use App\Domain\Community\Actions\VoteAction;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use Illuminate\Http\Request;

class ForumVoteController
{
    public function storeThreadVote(Request $request, ForumThread $thread, VoteAction $action): ForumThreadResource
    {
        $request->validate(['value' => ['required', 'integer', 'in:1,-1']]);

        $updatedThread = $action->voteThread(
            $request->user()->id,
            $thread->id,
            (int) $request->input('value')
        );

        return collect([new ForumThreadResource($updatedThread->load('votes'))])->first();
    }

    public function storeReplyVote(Request $request, ForumReply $reply, VoteAction $action): ForumReplyResource
    {
        $request->validate(['value' => ['required', 'integer', 'in:1,-1']]);

        $updatedReply = $action->voteReply(
            $request->user()->id,
            $reply->id,
            (int) $request->input('value')
        );

        return collect([new ForumReplyResource($updatedReply->load('votes'))])->first();
    }
}
