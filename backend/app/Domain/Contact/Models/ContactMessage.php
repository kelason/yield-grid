<?php

declare(strict_types=1);

namespace Domain\Contact\Models;

use App\Domain\Contact\Enums\ContactStatus;
use App\Domain\Contact\Models\ContactMessageReply;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ContactStatus $status
 * @property CarbonImmutable|null $replied_at
 */
class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'replied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'replied_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ContactMessageReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class, 'message_id')->orderBy('id');
    }
}
