<?php

declare(strict_types=1);

namespace Domain\Farming\Models;

use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Database\Factories\PlotFactory;
use Domain\Farming\Enums\SoilType;
use Domain\Farming\Enums\VerificationMethod;
use Domain\Farming\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Plot extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'polygon',
        'soil_type',
        'calculated_area',
        'agromonitoring_polyid',
        'verification_status',
        'verification_method',
        'verification_note',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'soil_type' => SoilType::class,
            'verification_status' => VerificationStatus::class,
            'verification_method' => VerificationMethod::class,
            'verified_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return PlotFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->verification_status ??= VerificationStatus::PENDING;
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('verification_status')) {
                return;
            }

            if ($model->getRawOriginal('verification_status') === VerificationStatus::REJECTED->value) {
                $model->verification_status = VerificationStatus::PENDING;
            }
        });
    }

    /**
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function recommendations()
    {
        return $this->hasMany(CropRecommendation::class);
    }

    public function getCentroid(): ?array
    {
        if ($this->id === null) {
            return null;
        }

        try {
            $result = DB::selectOne(
                'SELECT ST_Y(ST_Centroid(polygon::geometry)) as lat, ST_X(ST_Centroid(polygon::geometry)) as lon FROM plots WHERE id = ?',
                [$this->id]
            );
            if ($result && isset($result->lat) && isset($result->lon)) {
                return [
                    'lat' => (float) $result->lat,
                    'lon' => (float) $result->lon,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback for non-PostGIS or mock environments
        }

        return null;
    }
}
