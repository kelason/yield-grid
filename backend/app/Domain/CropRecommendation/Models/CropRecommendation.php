<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Models;

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use Domain\Farming\Models\Plot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropRecommendation extends Model
{
    protected $table = 'crop_recommendations';

    protected $fillable = [
        'plot_id',
        'crop_name',
        'confidence_score',
        'reasoning',
        'projected_yield',
        'status',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'status' => RecommendationStatus::class,
    ];

    /**
     * @return BelongsTo<Plot, $this>
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }
}
