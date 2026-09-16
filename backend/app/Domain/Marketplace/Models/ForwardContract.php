<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\CropRecommendation\Models\CropRecommendation;
use App\Domain\Marketplace\Enums\ContractStatus;
use Database\Factories\ForwardContractFactory;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

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

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::AVAILABLE);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', now())
            ->where('status', ContractStatus::AVAILABLE);
    }

    public function scopeByFarmer(Builder $query, int $farmerId): Builder
    {
        return $query->where('farmer_id', $farmerId);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function getIsPurchasableAttribute(): bool
    {
        return $this->status === ContractStatus::AVAILABLE && ! $this->is_expired;
    }
}
