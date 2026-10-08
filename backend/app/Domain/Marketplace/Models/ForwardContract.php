<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\ContractStatus;
use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Database\Factories\ForwardContractFactory;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ForwardContract extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ForwardContractFactory::new();
    }

    protected $fillable = [
        'farmer_id',
        'crop_recommendation_id',
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
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'total_price' => 'decimal:2',
        'estimated_harvest_date' => 'date',
        'expiry_date' => 'date',
        'status' => ContractStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * @return BelongsTo<CropRecommendation, $this>
     */
    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(CropRecommendation::class, 'crop_recommendation_id');
    }

    /**
     * @return HasOne<Purchase, $this>
     */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }

    /**
     * @return BelongsTo<ForwardContract, $this>
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
