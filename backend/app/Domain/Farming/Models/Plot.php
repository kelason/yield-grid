<?php

declare(strict_types=1);

namespace Domain\Farming\Models;

use App\Infrastructure\CropRecommendation\Models\CropRecommendation;
use Domain\Farming\Enums\SoilType;
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
    ];

    protected function casts(): array
    {
        return [
            'soil_type' => SoilType::class,
        ];
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
