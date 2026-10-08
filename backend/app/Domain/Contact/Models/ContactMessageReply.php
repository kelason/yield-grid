<?php

declare(strict_types=1);

namespace App\Domain\Contact\Models;

use App\Domain\Contact\Enums\ReplyDeliveryStatus;
use Carbon\CarbonImmutable;
use Domain\Contact\Models\ContactMessage;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $message_id
 * @property int|null $admin_id
 * @property string $recipient
 * @property string $body
 * @property string $client_request_id
 * @property ReplyDeliveryStatus $delivery_status
 * @property int $attempts
 * @property CarbonImmutable|null $sending_started_at
 * @property int $delivery_generation
 * @property CarbonImmutable|null $sent_at
 * @property string|null $error_code
 */
final class ContactMessageReply extends Model
{
    protected $fillable = [
        'message_id',
        'admin_id',
        'recipient',
        'body',
        'client_request_id',
        'delivery_status',
        'attempts',
        'sending_started_at',
        'delivery_generation',
        'sent_at',
        'error_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_status' => ReplyDeliveryStatus::class,
            'attempts' => 'integer',
            'sending_started_at' => 'immutable_datetime',
            'delivery_generation' => 'integer',
            'sent_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<ContactMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class, 'message_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isOutstanding(): bool
    {
        return $this->delivery_status === ReplyDeliveryStatus::QUEUED
            || $this->delivery_status === ReplyDeliveryStatus::SENDING;
    }
}
