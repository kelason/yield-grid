<?php

declare(strict_types=1);

namespace Domain\Users\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical structured address. Only PSGC codes are stored (names are resolved
 * at read time via the cached PSGC service) so every attribute depends on the
 * address id alone (3NF).
 */
class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'region_code',
        'province_code',
        'city_municipality_code',
        'barangay_code',
        'street',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_default' => 'boolean',
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
    public function scopeLocated(Builder $query): Builder
    {
        return $query->whereNotNull('location');
    }

    /**
     * Order by nearest to the given point (PostGIS KNN on the geography column).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeNearestTo(Builder $query, float $latitude, float $longitude): Builder
    {
        return $query->located()->orderByRaw(
            'location <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography',
            [$longitude, $latitude]
        );
    }

    public function getHasCoordinatesAttribute(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
