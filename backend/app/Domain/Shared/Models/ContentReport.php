<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Enums\ContentReportReason;
use App\Domain\Shared\Enums\ContentReportStatus;
use App\Domain\Shared\Enums\ReportTargetType;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array{type: string, id: string, owner_id: string, title: string, excerpt: string, status: ?string, captured_at: string}|null $target_snapshot
 */
class ContentReport extends Model
{
    protected $fillable = [
        'user_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'version',
        'reviewed_by',
        'reviewed_at',
        'resolution_note',
        'outcome',
        'target_snapshot',
    ];

    protected $casts = [
        'reportable_type' => ReportTargetType::class,
        'reason' => ContentReportReason::class,
        'status' => ContentReportStatus::class,
        'target_snapshot' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
