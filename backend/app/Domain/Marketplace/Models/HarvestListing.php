<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\ContractStatus;
use Domain\Farming\Models\Farm;
use Domain\Farming\Models\Plot;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

class HarvestListing extends Model
{
    protected $fillable = [
        'farmer_id',
        'farm_id',
        'plot_id',
        'title',
        'description',
        'crop_name',
        'quantity_kg',
        'price_per_kg',
        'total_price',
        'currency',
        'estimated_harvest_date',
        'expiry_date',
        'status',
        'shelf_life_days',
        'is_harvest_available',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'total_price' => 'decimal:2',
        'estimated_harvest_date' => 'date',
        'expiry_date' => 'date',
        'status' => ContractStatus::class,
        'shelf_life_days' => 'integer',
        'is_harvest_available' => 'boolean',
        'hidden_at' => 'datetime',
        'moderation_root_id' => 'integer',
    ];

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * Effective visibility: hidden directly or suppressed through a hidden
     * moderation root. Null root means self. A missing root row fails closed.
     */
    public function isEffectivelyHidden(): bool
    {
        if ($this->hidden_at !== null) {
            return true;
        }

        $rootId = $this->moderation_root_id;

        if ($rootId === null || (int) $rootId === (int) $this->getKey()) {
            return false;
        }

        $root = $this->relationLoaded('moderationRoot')
            ? $this->getRelation('moderationRoot')
            : $this->moderationRoot()->first();

        return ! $root instanceof self || $root->hidden_at !== null;
    }

    /**
     * Lock the moderation root for the given listing. Callers must take this
     * lock before locking the listing row itself (root-before-item order).
     * Requires an enclosing transaction.
     */
    public static function findModerationRootLocked(int $id): self
    {
        $rootId = static::whereKey($id)->value('moderation_root_id') ?? $id;

        return static::whereKey((int) $rootId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * @return BelongsTo<Plot, $this>
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return BelongsTo<HarvestListing, $this>
     */
    public function moderationRoot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'moderation_root_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::AVAILABLE);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', today())
            ->where('status', ContractStatus::AVAILABLE);
    }

    public function scopeByFarmer(Builder $query, int $farmerId): Builder
    {
        return $query->where('farmer_id', $farmerId);
    }

    /**
     * Member-visible rows: neither directly hidden nor suppressed through a
     * hidden moderation root. No global scope on purpose so administrative
     * readers and owner history can still load hidden rows explicitly.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeVisible(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query->whereNull("{$table}.hidden_at")
            ->where(function (Builder $nested) use ($table): void {
                $nested->whereNull("{$table}.moderation_root_id")
                    ->orWhereExists(function (QueryBuilder $exists) use ($table): void {
                        $exists->selectRaw('1')
                            ->from("{$table} as roots")
                            ->whereColumn('roots.id', "{$table}.moderation_root_id")
                            ->whereNull('roots.hidden_at');
                    });
            });
    }

    /**
     * Effectively hidden rows: directly hidden or suppressed through a
     * hidden moderation root.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeHidden(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query->where(function (Builder $nested) use ($table): void {
            $nested->whereNotNull("{$table}.hidden_at")
                ->orWhereExists(function (QueryBuilder $exists) use ($table): void {
                    $exists->selectRaw('1')
                        ->from("{$table} as roots")
                        ->whereColumn('roots.id', "{$table}.moderation_root_id")
                        ->whereNotNull('roots.hidden_at');
                });
        });
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function getIsPurchasableAttribute(): bool
    {
        return $this->status === ContractStatus::AVAILABLE && ! $this->is_expired && ! $this->isEffectivelyHidden();
    }
}
