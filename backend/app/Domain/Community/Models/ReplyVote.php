<?php

declare(strict_types=1);

namespace App\Domain\Community\Models;

use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplyVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reply_id',
        'value',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ForumReply, $this>
     */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'reply_id');
    }
}
