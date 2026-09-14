<?php

declare(strict_types=1);

namespace App\Domain\CropRecommendation\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherCache extends Model
{
    protected $table = 'weather_cache';

    protected $fillable = [
        'latitude',
        'longitude',
        'weather_data',
        'expires_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'weather_data' => 'array',
        'expires_at' => 'datetime',
    ];
}
