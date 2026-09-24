<?php

declare(strict_types=1);

namespace App\Domain\Community\Models;

use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumThread extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'accepted_reply_id',
        'title',
        'body',
        'vote_score',
        'reply_count',
        'is_pinned',
        'is_locked',
        'is_anonymous',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'is_anonymous' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ForumCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<ForumReply, $this>
     */
    public function acceptedReply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'accepted_reply_id');
    }

    /**
     * @return BelongsToMany<ForumTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ForumTag::class, 'forum_thread_tag', 'thread_id', 'tag_id');
    }

    /**
     * @return HasMany<ForumReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'thread_id');
    }

    /**
     * @return HasMany<ThreadVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ThreadVote::class, 'thread_id');
    }

    /**
     * @return HasMany<ForumAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ForumAttachment::class, 'attachable_id')->where('attachable_type', 'thread');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }
}
