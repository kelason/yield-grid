<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Domain\Contact\Models\ContactMessageReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContactMessageReply
 */
final class ContactMessageReplyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'message_id' => (string) $this->message_id,
            'admin_id' => $this->admin_id !== null ? (string) $this->admin_id : null,
            'recipient' => $this->recipient,
            'body' => $this->body,
            'delivery_status' => $this->delivery_status->value,
            'attempts' => $this->attempts,
            'delivery_generation' => $this->delivery_generation,
            'sent_at' => $this->sent_at,
            'error_code' => $this->error_code,
            'created_at' => $this->created_at,
        ];
    }
}
