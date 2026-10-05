<?php

declare(strict_types=1);

namespace App\Domain\Insurance\Models;

use App\Domain\Insurance\Enums\ReminderType;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InsuranceReminderLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'reference_key',
        'meta',
        'enrollment_id',
        'claim_id',
        'sent_at',
    ];

    protected $casts = [
        'type' => ReminderType::class,
        'meta' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
