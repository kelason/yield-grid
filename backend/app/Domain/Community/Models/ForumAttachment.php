<?php

declare(strict_types=1);

namespace App\Domain\Community\Models;

use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attachable_type',
        'attachable_id',
        'file_path',
        'file_type',
        'file_size',
        'original_name',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
