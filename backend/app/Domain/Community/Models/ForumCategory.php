<?php

declare(strict_types=1);

namespace App\Domain\Community\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_emoji',
        'sort_order',
    ];

    /**
     * @return HasMany<ForumThread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'category_id');
    }
}
