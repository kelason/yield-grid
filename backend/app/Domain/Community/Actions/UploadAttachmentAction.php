<?php

declare(strict_types=1);

namespace App\Domain\Community\Actions;

use App\Domain\Community\Models\ForumAttachment;
use Illuminate\Http\UploadedFile;

final class UploadAttachmentAction
{
    public function execute(UploadedFile $file, int $userId, string $type, ?int $id = null): ForumAttachment
    {
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
}
