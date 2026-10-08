<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Constants\HttpCode;
use App\Domain\Community\Models\ForumAttachment;
use App\Domain\Community\Models\ForumReply;
use App\Domain\Community\Models\ForumThread;
use App\Domain\Shared\Enums\ReportTargetType;
use App\Policies\ForumContentPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;

final class UploadAttachmentAction
{
    public function execute(UploadedFile $file, int $userId, string $type, ?int $id = null): ForumAttachment
    {
        if ($id !== null) {
            $this->assertAttachable($type, $id, $userId);
        }

        $path = $file->store('attachments/forum', 'public');

        return ForumAttachment::create([
            'user_id' => $userId,
            'attachable_type' => $type,
            'attachable_id' => $id, // Nullable until the model is actually saved
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    private function assertAttachable(string $type, int $id, int $userId): void
    {
        $target = match (ReportTargetType::tryFrom($type)) {
            ReportTargetType::THREAD => ForumThread::whereKey($id)->first(),
            ReportTargetType::REPLY => ForumReply::whereKey($id)->first(),
            default => null,
        };

        if (! $this->isVisibleTarget($target)) {
            $model = $type === ReportTargetType::THREAD->value ? ForumThread::class : ForumReply::class;

            throw (new ModelNotFoundException)->setModel($model, $id);
        }

        if ($target->user_id !== $userId) {
            abort(HttpCode::FORBIDDEN, 'You can only attach files to your own content.');
        }
    }

    private function isVisibleTarget(?Model $target): bool
    {
        return match (true) {
            $target instanceof ForumThread => ForumContentPolicy::isThreadVisible($target),
            $target instanceof ForumReply => ForumContentPolicy::isReplyVisible($target),
            default => false,
        };
    }
}
