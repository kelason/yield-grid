<?php

declare(strict_types=1);

namespace App\Domain\Marketplace\Models;

use App\Domain\Marketplace\Enums\PriceSource;
use App\Domain\Marketplace\Enums\PriceTier;
use Illuminate\Database\Eloquent\Model;

class CropReferencePrice extends Model
{
    protected $fillable = [
        'crop_slug',
        'crop_display_name',
        'tier',
        'price_per_kg',
        'currency',
        'region_code',
        'market_name',
        'source',
        'observed_at',
    ];

    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'tier' => PriceTier::class,
        'source' => PriceSource::class,
        'observed_at' => 'date',
    ];
}
