<?php

namespace Domain\Farming\Models;

use Domain\Farming\Enums\SoilType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plot extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'polygon',
        'soil_type',
        'calculated_area',
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
}
