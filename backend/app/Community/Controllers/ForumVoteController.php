<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Resources\ForumReplyResource;
use App\Community\Resources\ForumThreadResource;
use App\Constants\ForumConstants;
use App\Domain\Community\Actions\VoteAction;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ForumVoteController
{
    public function storeThreadVote(Request $request, ForumThread $thread, VoteAction $action): ForumThreadResource
    {
        $request->validate(['value' => ['required', 'integer', Rule::in(ForumConstants::VOTE_VALUES)]]);

        $updatedThread = $action->voteThread(
            $request->user()->id,
            $thread->id,
            (int) $request->input('value')
        );

        return collect([new ForumThreadResource($updatedThread->load('votes'))])->first();
    }

    public function storeReplyVote(Request $request, ForumReply $reply, VoteAction $action): ForumReplyResource
    {
        $request->validate(['value' => ['required', 'integer', Rule::in(ForumConstants::VOTE_VALUES)]]);

        $updatedReply = $action->voteReply(
            $request->user()->id,
            $reply->id,
            (int) $request->input('value')
        );

        return collect([new ForumReplyResource($updatedReply->load('votes'))])->first();
    }
}
