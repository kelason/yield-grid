<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\ContractStatus;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HarvestListing extends Model
{
    protected $fillable = [
        'farmer_id',
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
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
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

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function getIsPurchasableAttribute(): bool
    {
        return $this->status === ContractStatus::AVAILABLE && ! $this->is_expired;
    }
}
