<?php

declare(strict_types=1);

namespace App\Insurance\Resources;

use App\Domain\Insurance\Models\InsuranceReminderLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InsuranceReminderLog
 */
final class InsuranceReminderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = 'insurance.reminders.'.$this->type->value;

        return [
            'type' => $this->type->value,
            'title_key' => $base.'.title',
            'message_key' => $base.'.message',
            'params' => $this->meta ?? [],
            'sent_at' => $this->sent_at->toIso8601String(),
        ];
    }
}
