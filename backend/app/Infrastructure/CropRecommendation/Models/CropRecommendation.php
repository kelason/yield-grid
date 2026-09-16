<?php

declare(strict_types=1);

namespace App\Infrastructure\CropRecommendation\Models;

use App\Domain\CropRecommendation\Enums\RecommendationStatus;
use App\Domain\Marketplace\Models\ForwardContract;
use Domain\Farming\Models\Plot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * @return HasOne<ForwardContract, $this>
     */
    public function forwardContract(): HasOne
    {
        return $this->hasOne(ForwardContract::class, 'crop_recommendation_id');
    }

    public function getIsPublishedAttribute(): bool
    {
        if ($this->relationLoaded('forwardContract')) {
            return $this->forwardContract !== null;
        }
        return $this->forwardContract()->exists();
    }
}
