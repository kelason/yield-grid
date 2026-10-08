<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\DemandStatus;
use Domain\Users\Models\User;
use Domain\Users\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropDemand extends Model
{
    protected $fillable = [
        'buyer_id',
        'address_id',
        'title',
        'description',
        'crop_name',
        'quantity_kg',
        'remaining_quantity_kg',
        'target_price_per_kg',
        'total_budget',
        'currency',
        'needed_by_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'remaining_quantity_kg' => 'decimal:2',
        'target_price_per_kg' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'needed_by_date' => 'date',
        'expiry_date' => 'date',
        'status' => DemandStatus::class,
        'hidden_at' => 'datetime',
    ];

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<UserAddress, $this>
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    /**
     * @return HasMany<CropDemandOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(CropDemandOffer::class, 'crop_demand_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('crop_demands.status', DemandStatus::OPEN);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeByBuyer(Builder $query, int $buyerId): Builder
    {
        return $query->where('crop_demands.buyer_id', $buyerId);
    }

    /**
     * Member-visible demands: not hidden. No global scope on purpose so
     * administrative readers and existing parties can still load hidden rows.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('crop_demands.hidden_at');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->whereNotNull('crop_demands.hidden_at');
    }

    /**
     * Order demands nearest to the given coordinates first (PostGIS KNN on the
     * delivery-point geography column) and expose each row's distance in meters.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeNearestTo(Builder $query, float $latitude, float $longitude): Builder
    {
        return $query
            ->join('user_addresses as demand_addresses', 'crop_demands.address_id', '=', 'demand_addresses.id')
            ->whereNotNull('demand_addresses.location')
            ->select('crop_demands.*')
            ->selectRaw(
                'ST_Distance(demand_addresses.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_m',
                [$longitude, $latitude]
            )
            ->orderBy('distance_m');
    }

    /**
     * Fallback ranking when no coordinates are available: same city first,
     * then same region, then newest.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeClosestToArea(Builder $query, string $cityCode, string $regionCode): Builder
    {
        return $query
            ->join('user_addresses as demand_addresses', 'crop_demands.address_id', '=', 'demand_addresses.id')
            ->select('crop_demands.*')
            ->orderByRaw('(demand_addresses.city_municipality_code = ?) DESC', [$cityCode])
            ->orderByRaw('(demand_addresses.region_code = ?) DESC', [$regionCode])
            ->orderByDesc('crop_demands.created_at');
    }

    /**
     * Nearest-first ordering derived from a saved address: exact coordinates
     * when present, area-code fallback otherwise.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeClosestToAddress(Builder $query, UserAddress $address): Builder
    {
        if ($address->latitude !== null && $address->longitude !== null) {
            return $query->nearestTo((float) $address->latitude, (float) $address->longitude);
        }

        return $query->closestToArea($address->city_municipality_code, $address->region_code);
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === DemandStatus::OPEN && ! $this->expiry_date->isPast();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->isPast();
    }
}
