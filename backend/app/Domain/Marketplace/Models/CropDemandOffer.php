<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CropDemandOffer extends Model
{
    protected $fillable = [
        'crop_demand_id',
        'farmer_id',
        'quantity_kg',
        'price_per_kg',
        'total_price',
        'currency',
        'message',
        'status',
        'accepted_at',
        'paid_at',
        'delivered_at',
        'completed_at',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'total_price' => 'decimal:2',
        'status' => DemandOfferStatus::class,
        'accepted_at' => 'datetime',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<CropDemand, $this>
     */
    public function demand(): BelongsTo
    {
        return $this->belongsTo(CropDemand::class, 'crop_demand_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * @return HasOne<Purchase, $this>
     */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class, 'crop_demand_offer_id')->latestOfMany();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeChatEligible(Builder $query): Builder
    {
        return $query->whereIn('status', DemandOfferStatus::chatEligible());
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeBlockingNewOffer(Builder $query): Builder
    {
        return $query->whereIn('status', DemandOfferStatus::blockingNewOffer());
    }

    public function getIsPayableAttribute(): bool
    {
        return $this->status === DemandOfferStatus::ACCEPTED;
    }
}
