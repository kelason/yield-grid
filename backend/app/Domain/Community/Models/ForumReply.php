<?php

declare(strict_types=1);

namespace App\Domain\Community\Models;

use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thread_id',
        'user_id',
        'parent_id',
        'body',
        'vote_score',
        'is_accepted',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'is_accepted' => 'boolean',
            'is_anonymous' => 'boolean',
            'hidden_at' => 'datetime',
        ];
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * Member-visible replies: not hidden, on a visible thread, with a visible
     * (or absent) direct parent. Deeper ancestry is enforced by
     * ForumContentPolicy pruning, which every member reader applies.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        // The parent column stays unqualified on purpose: the self-join
        // aliases the parent table, so a table-qualified name would resolve
        // to the outer reply row and never filter hidden parents.
        return $query->whereNull('forum_replies.hidden_at')
            ->whereHas('thread', fn (Builder $thread) => $thread->visible())
            ->where(function (Builder $nested): void {
                $nested->whereNull('forum_replies.parent_id')
                    ->orWhereHas('parent', fn (Builder $parent) => $parent->whereNull('hidden_at'));
            });
    }

    /**
     * @return BelongsTo<ForumThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ReplyVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ReplyVote::class, 'reply_id');
    }

    /**
     * @return HasMany<ForumAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ForumAttachment::class, 'attachable_id')->where('attachable_type', 'reply');
    }
}
