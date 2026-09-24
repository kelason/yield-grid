<?php

declare(strict_types=1);

namespace App\Domain\Chat\Models;

use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ChatConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the number of unread messages for this participant.
     */
    public function unreadCount(): int
    {
        $query = $this->conversation->messages()
            ->where('user_id', '!=', $this->user_id);

        if ($this->last_read_at) {
            $query->where('created_at', '>', $this->last_read_at);
        }

        return $query->count();
    }
}
