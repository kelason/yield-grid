<?php

declare(strict_types=1);

namespace App\Domain\Community\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ForumTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @return BelongsToMany<ForumThread, $this>
     */
    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(ForumThread::class, 'forum_thread_tag', 'tag_id', 'thread_id');
    }
}
