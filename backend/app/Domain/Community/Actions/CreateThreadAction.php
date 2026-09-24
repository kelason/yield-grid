<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Community\DTOs\CreateThreadData;
use App\Domain\Community\Models\ForumThread;

final class CreateThreadAction
{
    public function execute(CreateThreadData $data): ForumThread
    {
        $thread = ForumThread::create([
            'user_id' => $data->userId,
            'category_id' => $data->categoryId,
            'title' => $data->title,
            'body' => $data->body,
            'is_anonymous' => $data->isAnonymous,
            'last_activity_at' => now(),
        ]);

        if (! empty($data->tagIds)) {
            $thread->tags()->sync($data->tagIds);
        }

        return $thread;
    }
}
