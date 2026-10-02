<?php

declare(strict_types=1);

namespace App\Domain\CreditScoring\Models;

use App\Domain\CreditScoring\Enums\ReportStatus;
use App\Domain\CreditScoring\Enums\ScoreTier;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CreditScoreSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'overall_score',
        'tier',
        'dimension_scores',
        'raw_metrics',
        'report_status',
        'report_path',
        'report_token',
        'report_generated_at',
        'report_expires_at',
    ];

    protected $casts = [
        'overall_score' => 'integer',
        'tier' => ScoreTier::class,
        'dimension_scores' => 'array',
        'raw_metrics' => 'array',
        'report_status' => ReportStatus::class,
        'report_generated_at' => 'datetime',
        'report_expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeLatestSnapshot(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function getIsReportExpiredAttribute(): bool
    {
        if ($this->report_expires_at === null) {
            return true;
        }

        return $this->report_expires_at->isPast();
    }

    public function getIsReportReadyAttribute(): bool
    {
        return $this->report_status === ReportStatus::READY
            && $this->report_path !== null
            && ! $this->is_report_expired;
    }
}
