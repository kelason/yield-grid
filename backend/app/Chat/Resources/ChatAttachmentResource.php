<?php

declare(strict_types=1);

namespace App\Chat\Resources;

use App\Domain\Chat\Models\ChatAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatAttachment
 */
class ChatAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_path' => asset('storage/'.$this->file_path),
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'original_name' => $this->original_name,
        ];
    }
}
