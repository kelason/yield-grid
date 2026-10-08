<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use Domain\Contact\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContactMessage
 */
final class AdminContactMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status->value,
            'replied_at' => $this->replied_at,
            'created_at' => $this->created_at,
            'replies' => ContactMessageReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
