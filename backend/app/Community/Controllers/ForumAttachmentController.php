<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\UploadAttachmentRequest;
use App\Community\Resources\ForumAttachmentResource;
use App\Domain\Community\Actions\UploadAttachmentAction;

class ForumAttachmentController
{
    public function store(UploadAttachmentRequest $request, UploadAttachmentAction $action): ForumAttachmentResource
    {
        $attachment = $action->execute(
            $request->file('file'),
            $request->user()->id,
            $request->validated('attachable_type'),
            $request->validated('attachable_id') ? (int) $request->validated('attachable_id') : null
        );

        return collect([new ForumAttachmentResource($attachment)])->first();
    }
}
